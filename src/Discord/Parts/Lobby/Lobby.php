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

namespace Discord\Parts\Lobby;

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Invite;
use Discord\Parts\Part;
use Discord\Parts\User\Member as UserMember;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;

/**
 * Represents a lobby within Discord. See Managing Lobbies for more information.
 *
 * @since 10.28.0
 *
 * @link https://docs.discord.com/developers/resources/lobby#lobby-object
 * @link https://docs.discord.com/developers/discord-social-sdk/development-guides/managing-lobbies
 *
 * @property      string                                 $id             The unique identifier of the lobby.
 * @property      string                                 $application_id The application that created the lobby.
 * @property      array|null                             $metadata       Dictionary of string key/value pairs. The max total length is 1000.
 * @property      ExCollectionInterface<Member>|Member[] $members        Members of the lobby.
 * @property-read Channel|null                           $linked_channel The guild channel linked to the lobby.
 */
class Lobby extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'application_id',
        'metadata',
        'members',
        'linked_channel',
    ];

    /**
     * Adds a user to the lobby, or updates their metadata, flags or additional name if they are already a member.
     *
     * @param UserMember|User|string $user The member, user, or user id to add.
     * @param array                  $data See {@see \Discord\Repository\LobbyRepository::addMember()}.
     *
     * @return PromiseInterface<Member>
     *
     * @since 10.59.0
     */
    public function addMember($user, array $data = []): PromiseInterface
    {
        return $this->discord->lobbies->addMember($this, $user, $data);
    }

    /**
     * Removes a user from the lobby.
     *
     * @param UserMember|User|string $user The member, user, or user id to remove.
     *
     * @return PromiseInterface
     *
     * @since 10.59.0
     */
    public function removeMember($user): PromiseInterface
    {
        return $this->discord->lobbies->removeMember($this, $user);
    }

    /**
     * Adds, updates and removes up to 25 members in one request.
     *
     * @param array[]|Member[] $members See {@see \Discord\Repository\LobbyRepository::bulkUpdateMembers()}.
     *
     * @return PromiseInterface<ExCollectionInterface<Member>|Member[]> The members that were added or updated.
     *
     * @since 10.59.0
     */
    public function bulkUpdateMembers(array $members): PromiseInterface
    {
        return $this->discord->lobbies->bulkUpdateMembers($this, $members);
    }

    /**
     * Creates an invite for a lobby member to the channel linked to the lobby.
     *
     * @param UserMember|User|string $user The member, user, or user id to invite.
     *
     * @return PromiseInterface<Invite>
     *
     * @since 10.59.0
     */
    public function createInvite($user): PromiseInterface
    {
        return $this->discord->lobbies->createInvite($this, $user);
    }

    /**
     * Gets the members attribute.
     *
     * @return ExCollectionInterface<Member>|Member[] The members of the lobby.
     *
     * @since 10.59.0
     */
    protected function getMembersAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('members', Member::class);
    }

    /**
     * Gets the linked_channel attribute.
     *
     * @return Channel|null The guild channel linked to the lobby.
     */
    protected function getLinkedChannelAttribute(): ?Channel
    {
        return $this->attributePartHelper('linked_channel', Channel::class);
    }
}
