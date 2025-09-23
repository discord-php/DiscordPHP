<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Http\Endpoint;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Lobby\Lobby;
use Discord\Parts\Lobby\Member;
use Discord\Parts\User\Member as UserMember;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;

/**
 * Contains lobbies.
 *
 * @see Lobby
 *
 * @since 10.28.0
 *
 * @method Lobby|null get(string $discrim, $key)
 * @method Lobby|null pull(string|int $key, $default = null)
 * @method Lobby|null first()
 * @method Lobby|null last()
 * @method Lobby|null find(callable $callback)
 */
class LobbyRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        //'create' => Endpoint::LOBBIES,
        'get' => Endpoint::LOBBY,
        'update' => Endpoint::LOBBY,
        'delete' => Endpoint::LOBBY,
        'leave' => Endpoint::LOBBY_SELF,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Lobby::class;

    /**
     * Creates a new lobby, adding any of the specified members to it, if provided.
     *
     * @param array     $data
     * @param ?array    $data['metadata']             Optional dictionary of string key/value pairs. The max total length is 1000.
     * @param ?Member[] $data['members']              Optional array of up to 25 users to be added to the lobby.
     * @param ?int      $data['idle_timeout_seconds'] Seconds to wait before shutting down a lobby after it becomes idle. Value can be between 5 and 604800 (7 days). See LobbyHandle for more details on this behavior.
     */
    protected function createLobby($data = [])
    {
        return $this->http->post(Endpoint::LOBBIES, $data)
            ->then(fn ($response) => $this->factory->part($this->class, $response, true));
    }

    /**
     * Adds the provided user to the specified lobby.
     *
     * If called when the user is already a member of the lobby will update fields such as metadata on that user instead.
     *
     * @param Lobby|string           $lobby            The lobby or lobby id to add the member to.
     * @param UserMember|User|string $user             Member, user, or user id to add to the lobby.
     * @param array                  $data
     * @param ?array                 $data['metadata'] Optional dictionary of string key/value pairs. The max total length is 1000.
     * @param ?int                   $data['flags']    Lobby member flags combined as a bitfield.
     *
     * @return PromiseInterface<Member>
     */
    protected function addMember($lobby, $user, $data = []): PromiseInterface
    {
        if (! is_string($lobby)) {
            $lobby = $lobby->id;
        }

        if (! is_string($user)) {
            $user = $user->id;
        }

        return $this->http->put(Endpoint::bind(Endpoint::LOBBY_MEMBER, $lobby, $user), $data)
            ->then(fn ($response) => $this->factory->part(Member::class, $response, true));
    }

    /**
     * Removes the provided user from the specified lobby.
     *
     * It is safe to call this even if the user is no longer a member of the lobby, but will fail if the lobby does not exist.
     *
     * @param Lobby|string           $lobby The lobby or lobby id to remove the member from.
     * @param UserMember|User|string $user  Member, user, or user id to remove from the lobby.
     *
     * @return PromiseInterface
     */
    protected function removeMember($lobby, $user): PromiseInterface
    {
        if (! is_string($lobby)) {
            $lobby = $lobby->id;
        }

        if (! is_string($user)) {
            $user = $user->id;
        }

        return $this->http->delete(Endpoint::bind(Endpoint::LOBBY_MEMBER, $lobby, $user));
    }

    /**
     * Removes the current user from the specified lobby.
     *
     * It is safe to call this even if the user is no longer a member of the lobby, but will fail if the lobby does not exist.
     *
     * @param Lobby|string $lobby The lobby or lobby id to add the member to.
     *
     * @return PromiseInterface
     */
    protected function leave($lobby): PromiseInterface
    {
        if (! is_string($lobby)) {
            $lobby = $lobby->id;
        }

        return $this->http->delete(Endpoint::bind(Endpoint::LOBBY_SELF, $lobby));
    }

    /**
     * Links or unlinks a lobby to a channel.
     *
     * Uses Bearer token for authorization and user must be a lobby member with CanLinkLobby lobby member flag.
     *
     * @param Lobby|string        $lobby   The lobby or lobby id to link the channel to.
     * @param Channel|string|null $channel The channel or channel id to link the lobby to. If null, unlinks the lobby.
     *
     * @return PromiseInterface<Lobby>
     */
    protected function linkChannelLobby($lobby, $channel = null): PromiseInterface
    {
        if (! is_string($lobby)) {
            $lobby = $lobby->id;
        }

        if (! is_string($channel)) {
            $channel = $channel->id;
        }

        $payload = [];

        if ($channel !== null) {
            $payload['channel_id'] = $channel;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::LOBBY_CHANNEL_LINKING, $lobby, $channel), $payload)
            ->then(fn ($response) => $this->factory->part($this->class, $response, true));
    }
}
