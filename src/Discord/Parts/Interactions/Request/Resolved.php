<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Request;

use Discord\Helpers\Collection;
use Discord\Parts\Channel\Attachment;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * Represents the data associated with an interaction.
 *
 * @see https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-resolved-data-structure
 *
 * @property Collection|User[]|null             $users       The ids and User objects.
 * @property Collection|Member[]|null           $members     The ids and partial Member objects.
 * @property Collection|Role[]|null             $roles       The ids and Role objects.
 * @property Collection|Channel[]|Thread[]|null $channels    The ids and partial Channel objects.
 * @property Collection|Message[]|null          $messages    The ids and partial Message objects.
 * @property Collection|Attachment[]|null       $attachments The ids and partial Attachment objects.
 * @property string|null                        $guild_id    ID of the guild passed from Interaction.
 */
class Resolved extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['users', 'members', 'roles', 'channels', 'messages', 'attachments', 'guild_id'];

    /**
     * Returns a collection of resolved users.
     *
     * @return Collection|User[]|null Map of Snowflakes to user objects
     */
    protected function getUsersAttribute(): ?Collection
    {
        if (! isset($this->attributes['users'])) {
            return null;
        }

        $collection = Collection::for(User::class);

        foreach ($this->attributes['users'] as $snowflake => $user) {
            if (! $userPart = $this->discord->users->get('id', $snowflake)) {
                $userPart = $this->factory->create(User::class, $user, true);
            }

            $collection->push($userPart);
        }

        return $collection;
    }

    /**
     * Returns a collection of resolved members.
     *
     * Partial Member objects are missing user, deaf and mute fields
     *
     * @return Collection|Member[]|null Map of Snowflakes to partial member objects
     */
    protected function getMembersAttribute(): ?Collection
    {
        if (! isset($this->attributes['members'])) {
            return null;
        }

        $collection = Collection::for(Member::class);

        foreach ($this->attributes['members'] as $snowflake => $member) {
            if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
                $memberPart = $guild->members->get('id', $snowflake);
            }

            if (! $memberPart) {
                $member->user = $this->attributes['users']->$snowflake;
                $memberPart = $this->factory->create(Member::class, $member, true);
            }

            $collection->push($memberPart);
        }

        return $collection;
    }

    /**
     * Returns a collection of resolved roles.
     *
     * @return Collection|Role[]|null Map of Snowflakes to role objects
     */
    protected function getRolesAttribute(): ?Collection
    {
        if (! isset($this->attributes['roles'])) {
            return null;
        }

        $collection = Collection::for(Role::class);

        foreach ($this->attributes['roles'] as $snowflake => $role) {
            if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
                $rolePart = $guild->roles->get('id', $snowflake);
            }

            if (! $rolePart) {
                $rolePart = $this->factory->create(Role::class, $role, true);
            }

            $collection->push($rolePart);
        }

        return $collection;
    }

    /**
     * Returns a collection of resolved channels.
     *
     * Partial Channel objects only have id, name, type and permissions fields. Threads will also have thread_metadata and parent_id fields.
     *
     * @return Collection|Channel[]|Thread[]|null Map of Snowflakes to partial channel objects
     */
    protected function getChannelsAttribute(): ?Collection
    {
        if (! isset($this->attributes['channels'])) {
            return null;
        }

        $collection = new Collection();

        foreach ($this->attributes['channels'] as $snowflake => $channel) {
            if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
                $channelPart = $guild->channels->get('id', $snowflake);
            }

            if (! $channelPart) {
                if (in_array($channel->type, [Channel::TYPE_NEWS_THREAD, Channel::TYPE_PRIVATE_THREAD, Channel::TYPE_PUBLIC_THREAD])) {
                    $channelPart = $this->factory->create(Thread::class, $channel, true);
                } else {
                    $channelPart = $this->factory->create(Channel::class, $channel, true);
                }
            }

            $collection->push($channelPart);
        }

        return $collection;
    }

    /**
     * Returns a collection of resolved messages.
     *
     * @return Collection|Message[]|null Map of Snowflakes to partial messages objects
     */
    protected function getMessagesAttribute(): ?Collection
    {
        if (! isset($this->attributes['messages'])) {
            return null;
        }

        $collection = Collection::for(Message::class);

        foreach ($this->attributes['messages'] as $snowflake => $message) {
            if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
                if ($channel = $guild->channels->get('id', $message->channel_id)) {
                    $messagePart = $channel->messages->get('id', $snowflake);
                }
            }

            if (! $messagePart) {
                $messagePart = $this->factory->create(Message::class, $message, true);
            }

            $collection->push($messagePart);
        }

        return $collection;
    }

    /**
     * Returns a collection of resolved attachments.
     *
     * @return Collection|Attachment[]|null Map of Snowflakes to attachments objects
     */
    protected function getAttachmentsAttribute(): ?Collection
    {
        if (! isset($this->attributes['attachments'])) {
            return null;
        }

        $attachments = Collection::for(Attachment::class);

        foreach ($this->attributes['attachments'] as $attachment) {
            $attachments->pushItem($this->factory->create(Attachment::class, $attachment, true));
        }

        return $attachments;
    }
}
