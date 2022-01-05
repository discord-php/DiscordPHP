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
use Discord\Parts\Part;
use Discord\Parts\Thread\Member;
use Discord\Parts\Thread\Thread;

/**
 * Represents the data associated with an interaction.
 *
 * @see https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-resolved-data-structure
 *
 * @property Collection|User[]|null             $users    The ids and User objects.
 * @property Collection|Member[]|null           $members  The ids and partial Member objects.
 * @property Collection|Role[]|null             $roles    The ids and Role objects.
 * @property Collection|Channel[]|Thread[]|null $channels The ids and partial Channel objects.
 * @property Collection|Message[]|null          $messages The ids and partial Message objects.
 */
class Resolved extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['users', 'members', 'roles', 'channels', 'messages'];

    /**
     * Returns a collection of resolved users.
     *
     * @return Collection|User[] Map of Snowflakes to user objects
     */
    protected function getUsersAttribute(): Collection
    {
        $collection = new Collection([]);

        foreach ($this->attributes['users'] ?? [] as $snowflake => $user) {
            if ($user = $this->discord->users->get('id', $snowflake)) {
                $collection->push($user);
            } else {
                $collection->push($this->factory->create(User::class, $user, true));
            }
        }

        return $collection;
    }

    /**
     * Returns a collection of resolved members.
     *
     * Partial Member objects are missing user, deaf and mute fields
     *
     * @return Collection|Member[] Map of Snowflakes to partial member objects
     */
    protected function getMembersAttribute(): Collection
    {
        $collection = new Collection([]);

        foreach ($this->attributes['members'] ?? [] as $snowflake => $member) {
            if ($guild = $this->discord->guilds->get('id', $member->guild_id)) {
                if (! $member = $guild->members->get('id', $snowflake)) {
                    $member = $this->factory->create(Member::class, $member, true);
                    $guild->members->push($member);
                }
            } else {
                $member = $this->factory->create(Member::class, $member, true);
            }

            $collection->push($member);
        }

        return $collection;
    }

    /**
     * Returns a collection of resolved roles.
     *
     * @return Collection|Roles[] Map of Snowflakes to role objects
     */
    protected function getRolesAttribute(): Collection
    {
        $collection = new Collection([]);

        foreach ($this->attributes['roles'] ?? [] as $snowflake => $role) {
            if ($guild = $this->discord->guilds->get('id', $role->guild_id)) {
                if (! $role = $guild->roles->get('id', $snowflake)) {
                    $role = $this->factory->create(Role::class, $role, true);
                    $guild->roles->push($role);
                }
            } else {
                $role = $this->factory->create(Role::class, $role, true);
            }

            $collection->push($role);
        }

        return $collection;
    }

    /**
     * Returns a collection of resolved channels.
     *
     * Partial Channel objects only have id, name, type and permissions fields. Threads will also have thread_metadata and parent_id fields.
     *
     * @return Collection|Channels[]|Threads[] Map of Snowflakes to partial channel objects
     */
    protected function getChannelsAttribute(): Collection
    {
        $collection = new Collection([]);

        foreach ($this->attributes['channels'] ?? [] as $snowflake => $channel) {
            if ($guild = $this->discord->guilds->get('id', $channel->guild_id)) {
                if (! $channel = $guild->channels->get('id', $snowflake)) {
                    if ($channel instanceof Thread) {
                        $channel = $this->factory->create(Thread::class, $channel, true);
                    } else {
                        $channel = $this->factory->create(Channel::class, $channel, true);
                    }
                    $guild->channels->push($channel);
                }
            } else {
                $channel = $this->factory->create(Channel::class, $channel, true);
            }

            $collection->push($channel);
        }

        return $collection;
    }

    /**
     * Returns a collection of resolved messages.
     *
     * @return Collection|Message[] Map of Snowflakes to partial messages objects
     */
    protected function getMessagesAttribute(): Collection
    {
        $collection = new Collection([]);

        foreach ($this->attributes['messages'] ?? [] as $snowflake => $message) {
            if ($channel = $this->discord->getChannel($message->channel_id)) {
                if (! $message = $channel->messages->get('id', $snowflake)) {
                    $message = $this->factory->create(Message::class, $message, true);
                    $channel->messages->push($message);
                }
            } else {
                $message = $this->factory->create(Message::class, $message, true);
            }

            $collection->push($message);
        }

        return $collection;
    }
}
