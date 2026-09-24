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
use Discord\Parts\Channel\Invite;
use Discord\Parts\Lobby\Lobby;
use Discord\Parts\Lobby\Member;
use Discord\Parts\Lobby\Message;
use Discord\Parts\User\Member as UserMember;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;

/**
 * Contains lobbies.
 *
 * Everything here is done with the bot token, as a game's backend does it.
 * What a player does in a lobby — creating or joining one by secret, leaving,
 * linking a channel, sending and reading messages — uses the player's own
 * OAuth2 token instead.
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
     *
     * @return PromiseInterface<Lobby>
     */
    public function createLobby($data = []): PromiseInterface
    {
        return $this->http->post(Endpoint::LOBBIES, $data)
            ->then(function ($response) {
                $lobby = $this->factory->part($this->class, (array) $response, true);

                return $this->cache->set($lobby->id, $lobby)->then(static fn ($success) => $lobby);
            });
    }

    /**
     * Modifies the specified lobby with new values, if provided.
     *
     * @param Lobby|string $lobby                        The lobby or lobby id to modify.
     * @param array        $data
     * @param ?array       $data['metadata']             Optional dictionary of string key/value pairs. The max total length is 1000.
     * @param ?Member[]    $data['members']              Optional array of up to 25 users to replace the lobby members with.
     * @param ?int         $data['idle_timeout_seconds'] Seconds to wait before shutting down a lobby after it becomes idle. Value can be between 5 and 604800 (7 days). See LobbyHandle for more details on this behavior.
     *
     * @return PromiseInterface<Lobby>
     */
    public function modifyLobby($lobby, $data = []): PromiseInterface
    {
        if (! is_string($lobby)) {
            $lobby = $lobby->id;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::LOBBY, $lobby), $data)
            ->then(function ($response) {
                $lobby = $this->factory->part($this->class, (array) $response, true);

                return $this->cache->set($lobby->id, $lobby)->then(static fn ($success) => $lobby);
            });
    }

    /**
     * Adds the provided user to the specified lobby.
     *
     * If called when the user is already a member of the lobby will update fields such as metadata on that user instead.
     *
     * @param Lobby|string           $lobby                   The lobby or lobby id to add the member to.
     * @param UserMember|User|string $user                    Member, user, or user id to add to the lobby.
     * @param array                  $data
     * @param ?array                 $data['metadata']        Optional dictionary of string key/value pairs. The max total length is 1000.
     * @param ?int                   $data['flags']           Lobby member flags combined as a bitfield.
     * @param ?string                $data['additional_name'] An additional 1-80 character display name for the member, such as an in-game character name. Null clears it; omit it to keep the current value.
     *
     * @return PromiseInterface<Member>
     */
    public function addMember($lobby, $user, $data = []): PromiseInterface
    {
        if (! is_string($lobby)) {
            $lobby = $lobby->id;
        }

        if (! is_string($user)) {
            $user = $user->id;
        }

        return $this->http->put(Endpoint::bind(Endpoint::LOBBY_MEMBER, $lobby, $user), $data)
            ->then(fn ($response) => $this->factory->part(Member::class, (array) $response, true));
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
    public function removeMember($lobby, $user): PromiseInterface
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
     * Adds, updates and removes up to 25 lobby members in one request.
     *
     * Each entry is a {@see Member}, or an array with an `id` and any of `metadata`, `flags`, `additional_name`,
     * and `remove_member` (true to remove the user rather than add or update them).
     *
     * @param Lobby|string     $lobby   The lobby or lobby id whose members to update.
     * @param array[]|Member[] $members The members to add, update or remove.
     *
     * @return PromiseInterface<ExCollectionInterface<Member>|Member[]> The members that were added or updated. Removed members are not included.
     *
     * @since 10.59.0
     */
    public function bulkUpdateMembers($lobby, array $members): PromiseInterface
    {
        if (! is_string($lobby)) {
            $lobby = $lobby->id;
        }

        $payload = array_map(
            static fn ($member) => $member instanceof Member ? $member->getRawAttributes() : $member,
            array_values($members)
        );

        return $this->http->post(Endpoint::bind(Endpoint::LOBBY_MEMBERS_BULK, $lobby), $payload)
            ->then(function ($response) {
                /** @var ExCollectionInterface<Member> $collection */
                $collection = $this->discord->getCollectionClass()::for(Member::class);

                foreach ((array) $response as $member) {
                    $collection->pushItem($this->factory->part(Member::class, (array) $member, true));
                }

                return $collection;
            });
    }

    /**
     * Creates an invite for a lobby member to the channel linked to the lobby.
     *
     * @param Lobby|string           $lobby The lobby or lobby id whose linked channel to invite to.
     * @param UserMember|User|string $user  Member, user, or user id of the lobby member to invite.
     *
     * @return PromiseInterface<Invite> An invite with only its `code`.
     *
     * @since 10.59.0
     */
    public function createInvite($lobby, $user): PromiseInterface
    {
        if (! is_string($lobby)) {
            $lobby = $lobby->id;
        }

        if (! is_string($user)) {
            $user = $user->id;
        }

        return $this->http->post(Endpoint::bind(Endpoint::LOBBY_MEMBER_INVITES, $lobby, $user))
            ->then(fn ($response) => $this->factory->part(Invite::class, (array) $response, true));
    }

    /**
     * Sets the moderation metadata on a lobby message, which is delivered to the players' clients.
     *
     * @param Lobby|string   $lobby    The lobby or lobby id the message was sent in.
     * @param Message|string $message  The lobby message or its id.
     * @param array          $metadata Up to 5 free-form string key/value pairs describing the decision, e.g. `['action' => 'hide', 'reason' => 'toxicity']`.
     *
     * @return PromiseInterface
     *
     * @since 10.59.0
     */
    public function updateMessageModerationMetadata($lobby, $message, array $metadata): PromiseInterface
    {
        if (! is_string($lobby)) {
            $lobby = $lobby->id;
        }

        if (! is_string($message)) {
            $message = $message->id;
        }

        return $this->http->put(Endpoint::bind(Endpoint::LOBBY_MESSAGE_MODERATION_METADATA, $lobby, $message), $metadata);
    }
}
