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

namespace Discord\Parts\Interactions\Request;

use Discord\Builders\ChannelBuilder;
use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Attachment;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * Represents the data associated with an interaction.
 *
 * @link https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-resolved-data-structure
 *
 * @since 7.0.0
 *
 * @property ExCollectionInterface<User>|User[]                $users       The ids and User objects.
 * @property ExCollectionInterface<Member>|Member[]            $members     The ids and partial Member objects.
 * @property ExCollectionInterface<Role>|Role[]                $roles       The ids and Role objects.
 * @property ExCollectionInterface<Channel>|Channel[]|Thread[] $channels    The ids and partial Channel objects.
 * @property ExCollectionInterface<Message>|Message[]          $messages    The ids and partial Message objects.
 * @property ExCollectionInterface<Attachment>|Attachment[]    $attachments The ids and partial Attachment objects.
 *
 * @property      string|null $guild_id ID of the guild internally passed from Interaction.
 * @property-read ?Guild|null $guild    The guild the interaction was sent in.
 */
class Resolved extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'users',
        'members',
        'roles',
        'channels',
        'messages',
        'attachments',

        // @internal
        'guild_id',
    ];

    /**
     * @inheritDoc
     */
    protected $hidden = ['guild_id'];

    /**
     * Returns a collection of resolved users.
     *
     * @return ExCollectionInterface<User>|User[] Map of Snowflakes to user objects
     */
    protected function getUsersAttribute(): ExCollectionInterface
    {
        if (isset($this->attributes['users']) && $this->attributes['users'] instanceof ExCollectionInterface) {
            return $this->attributes['users'];
        }

        $collection = Collection::for(User::class);

        if (! isset($this->attributes['users'])) {
            return $collection;
        }

        foreach ($this->attributes['users'] as $snowflake => $user) {
            $collection->pushItem($this->discord->users->get('id', $snowflake) ?? $this->factory->part(User::class, (array) $user, true));
        }

        $this->attributes['users'] = $collection;

        return $collection;
    }

    /**
     * Returns a collection of resolved members.
     *
     * Partial Member objects are missing user, deaf and mute fields
     *
     * @return ExCollectionInterface<Member>|Member[] Map of Snowflakes to partial member objects
     */
    protected function getMembersAttribute(): ExCollectionInterface
    {
        if (isset($this->attributes['members']) && $this->attributes['members'] instanceof ExCollectionInterface) {
            return $this->attributes['members'];
        }

        $collection = Collection::for(Member::class);

        if (! isset($this->attributes['members'])) {
            return $collection;
        }

        foreach ($this->attributes['members'] as $snowflake => $member) {
            if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
                $memberPart = $guild->members->get('id', $snowflake);
            }

            if (! isset($memberPart)) {
                $member->user = $this->attributes['users']->$snowflake;
                $memberPart = $this->factory->part(Member::class, (array) $member + ['guild_id' => $this->guild_id], true);
            }

            $collection->pushItem($memberPart);
        }

        $this->attributes['members'] = $collection;

        return $collection;
    }

    /**
     * Returns a collection of resolved roles.
     *
     * @return ExCollectionInterface<Role>|Role[] Map of Snowflakes to role objects
     */
    protected function getRolesAttribute(): ExCollectionInterface
    {
        if (isset($this->attributes['roles']) && $this->attributes['roles'] instanceof ExCollectionInterface) {
            return $this->attributes['roles'];
        }

        $collection = Collection::for(Role::class);

        if (! isset($this->attributes['roles'])) {
            return $collection;
        }

        foreach ($this->attributes['roles'] as $snowflake => $role) {
            if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
                $rolePart = $guild->roles->get('id', $snowflake);
            }

            if (! isset($rolePart)) {
                $rolePart = $this->factory->part(Role::class, (array) $role + ['guild_id' => $this->guild_id], true);
            }

            $collection->pushItem($rolePart);
        }

        $this->attributes['roles'] = $collection;

        return $collection;
    }

    /**
     * Returns a collection of resolved channels.
     *
     * Partial Channel objects only have id, name, type and permissions fields. Threads will also have thread_metadata and parent_id fields.
     *
     * @return ExCollectionInterface<Channel|Thread>|Channel[]|Thread[] Map of Snowflakes to partial channel objects
     */
    protected function getChannelsAttribute(): ExCollectionInterface
    {
        if (isset($this->attributes['channels']) && $this->attributes['channels'] instanceof ExCollectionInterface) {
            return $this->attributes['channels'];
        }

        $collection = new Collection();

        if (! isset($this->attributes['channels'])) {
            return $collection;
        }

        foreach ($this->attributes['channels'] as $snowflake => $channel) {
            if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
                $channelPart = $guild->channels->get('id', $snowflake);
            }

            if (! isset($channelPart)) {
                $channelPart = $this->factory->part(ChannelBuilder::TYPES[$channel->type] ?? Channel::class, (array) $channel + ['guild_id' => $this->guild_id], true);
            }

            $collection->pushItem($channelPart);
        }

        $this->attributes['channels'] = $collection;

        return $collection;
    }

    /**
     * Returns a collection of resolved messages.
     *
     * @return ExCollectionInterface<Message>|Message[] Map of Snowflakes to partial messages objects
     */
    protected function getMessagesAttribute(): ExCollectionInterface
    {
        if (isset($this->attributes['messages']) && $this->attributes['messages'] instanceof ExCollectionInterface) {
            return $this->attributes['messages'];
        }

        $collection = Collection::for(Message::class);

        if (! isset($this->attributes['messages'])) {
            return $collection;
        }

        foreach ($this->attributes['messages'] as $snowflake => $message) {
            if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
                if ($channel = $guild->channels->get('id', $message->channel_id)) {
                    $messagePart = $channel->messages->get('id', $snowflake);
                }
            }

            $collection->pushItem($messagePart ?? $this->factory->part(Message::class, (array) $message + ['guild_id' => $this->guild_id], true));
        }

        $this->attributes['messages'] = $collection;

        return $collection;
    }

    /**
     * Returns a collection of resolved attachments.
     *
     * @return ExCollectionInterface<Attachment>|Attachment[] Map of Snowflakes to attachments objects
     */
    protected function getAttachmentsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('attachments', Attachment::class);
    }

    protected function getGuildAttribute(): ?Guild
    {
        if (! isset($this->attributes['guild_id'])) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->attributes['guild_id']);
    }
}
