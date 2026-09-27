<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Helpers\ExCollectionInterface;
use Discord\Http\Endpoint;
use Discord\Http\Http;
use Discord\OAuth2\Session;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Invite;
use Discord\Parts\Lobby\Lobby;
use Discord\Parts\Lobby\Message;
use React\Promise\PromiseInterface;

/**
 * The lobbies a player is in, and what the player does in them.
 *
 * Everything here is sent with the player's own OAuth2 token, through their
 * {@see Session}. Managing lobbies from a game's backend — creating them,
 * adding and removing members — is done with the bot token instead, through
 * {@see LobbyRepository}.
 *
 * @see Lobby
 * @see Session::$lobbies
 *
 * @since 10.59.0
 *
 * @link https://docs.discord.com/developers/resources/lobby
 *
 * @method Lobby|null get(string $discrim, $key)
 * @method Lobby|null first()
 * @method Lobby|null last()
 * @method Lobby|null find(callable $callback)
 */
class SessionLobbyRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [];

    /**
     * @inheritDoc
     */
    protected $class = Lobby::class;

    /**
     * The player whose token every request here is sent with.
     */
    protected ?Session $session = null;

    /**
     * Binds the repository to a player's session.
     *
     * @internal Called by {@see Session} when it is created.
     */
    public function forSession(Session $session): static
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Joins the lobby with this secret, creating it first if there is none.
     *
     * @link https://docs.discord.com/developers/resources/lobby#create-or-join-lobby
     *
     * @param string  $secret                       Identifies the lobby within the application.
     * @param array   $data
     * @param ?int    $data['idle_timeout_seconds'] Seconds to wait before shutting down the lobby after it becomes idle, between 5 and 604800 (7 days).
     * @param ?array  $data['lobby_metadata']       String key/value pairs to set on the lobby, replacing what it had. The max total length is 1000.
     * @param ?array  $data['member_metadata']      String key/value pairs to set on the player's lobby member. The max total length is 1000.
     *
     * @return PromiseInterface<Lobby>
     */
    public function createOrJoin(string $secret, array $data = []): PromiseInterface
    {
        return $this->http()->put(Endpoint::LOBBIES, ['secret' => $secret] + $data)
            ->then($this->remember(...));
    }

    /**
     * Leaves a lobby.
     *
     * @link https://docs.discord.com/developers/resources/lobby#leave-lobby
     *
     * @param Lobby|string $lobby The lobby or lobby id.
     *
     * @return PromiseInterface
     */
    public function leave($lobby): PromiseInterface
    {
        $id = $this->idOf($lobby);

        return $this->http()->delete(Endpoint::bind(Endpoint::LOBBY_SELF, $id))
            ->then(fn () => $this->cache->delete($id));
    }

    /**
     * Links a guild text channel to a lobby, so the lobby's chat appears there.
     *
     * The player must be a lobby member with the `CanLinkLobby` flag.
     *
     * @link https://docs.discord.com/developers/resources/lobby#link-channel-to-lobby
     *
     * @param Lobby|string   $lobby   The lobby or lobby id.
     * @param Channel|string $channel The channel or channel id.
     *
     * @return PromiseInterface<Lobby>
     */
    public function linkChannel($lobby, $channel): PromiseInterface
    {
        return $this->http()->patch(Endpoint::bind(Endpoint::LOBBY_CHANNEL_LINKING, $this->idOf($lobby)), ['channel_id' => $this->idOf($channel)])
            ->then($this->remember(...));
    }

    /**
     * Unlinks whatever channel is linked to a lobby.
     *
     * The player must be a lobby member with the `CanLinkLobby` flag.
     *
     * @link https://docs.discord.com/developers/resources/lobby#unlink-channel-from-lobby
     *
     * @param Lobby|string $lobby The lobby or lobby id.
     *
     * @return PromiseInterface<Lobby>
     */
    public function unlinkChannel($lobby): PromiseInterface
    {
        return $this->http()->patch(Endpoint::bind(Endpoint::LOBBY_CHANNEL_LINKING, $this->idOf($lobby)), (object) [])
            ->then($this->remember(...));
    }

    /**
     * Sends a message to a lobby.
     *
     * @link https://docs.discord.com/developers/resources/lobby#send-lobby-message
     *
     * @param Lobby|string $lobby            The lobby or lobby id.
     * @param string       $content          The message content. Must not be empty.
     * @param array        $data
     * @param ?array       $data['metadata'] String key/value pairs delivered with the message to active clients. Not kept on the linked channel's copy.
     * @param ?int         $data['flags']    Message flags; only those the Social SDK can create are accepted.
     *
     * @return PromiseInterface<Message>
     */
    public function sendMessage($lobby, string $content, array $data = []): PromiseInterface
    {
        return $this->http()->post(Endpoint::bind(Endpoint::LOBBY_MESSAGES, $this->idOf($lobby)), ['content' => $content] + $data)
            ->then(fn ($response) => $this->factory->part(Message::class, (array) $response, true));
    }

    /**
     * Returns the most recent messages in a lobby the player is in.
     *
     * @link https://docs.discord.com/developers/resources/lobby#get-lobby-messages
     *
     * @param Lobby|string $lobby The lobby or lobby id.
     * @param int          $limit How many messages, between 1 and 200.
     *
     * @return PromiseInterface<ExCollectionInterface<Message>|Message[]>
     */
    public function getMessages($lobby, int $limit = 50): PromiseInterface
    {
        $endpoint = Endpoint::bind(Endpoint::LOBBY_MESSAGES, $this->idOf($lobby));
        $endpoint->addQuery('limit', $limit);

        return $this->http()->get($endpoint)
            ->then(function ($response) {
                /** @var ExCollectionInterface<Message> $collection */
                $collection = $this->discord->getCollectionClass()::for(Message::class);

                foreach ((array) $response as $message) {
                    $collection->pushItem($this->factory->part(Message::class, (array) $message, true));
                }

                return $collection;
            });
    }

    /**
     * Creates an invite for the player to the channel linked to a lobby.
     *
     * @link https://docs.discord.com/developers/resources/lobby#create-lobby-channel-invite-for-self
     *
     * @param Lobby|string $lobby The lobby or lobby id.
     *
     * @return PromiseInterface<Invite> An invite with only its `code`.
     */
    public function createInvite($lobby): PromiseInterface
    {
        return $this->http()->post(Endpoint::bind(Endpoint::LOBBY_MEMBER_ME_INVITES, $this->idOf($lobby)))
            ->then(fn ($response) => $this->factory->part(Invite::class, (array) $response, true));
    }

    /**
     * The session's client, looked up each time because refreshing the token replaces it.
     *
     * @throws \LogicException The repository was never bound to a session.
     */
    protected function http(): Http
    {
        if (null === $this->session) {
            throw new \LogicException('This repository acts as a player, and has no session to act as.');
        }

        return $this->session->getHttpClient();
    }

    /**
     * Hydrates a lobby and keeps it here.
     *
     * @param object|array $response
     *
     * @return PromiseInterface<Lobby>
     */
    protected function remember($response): PromiseInterface
    {
        $lobby = $this->factory->part(Lobby::class, (array) $response, true);

        return $this->cache->set($lobby->id, $lobby)->then(static fn () => $lobby);
    }

    /**
     * The id of a part, or the id itself.
     *
     * @param \Discord\Parts\Part|string $part
     */
    protected function idOf($part): string
    {
        return is_string($part) ? $part : $part->id;
    }
}
