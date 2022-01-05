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
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Part;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\User;

/**
 * Represents the data associated with an interaction.
 *
 * @see https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-resolved-data-structure
 *
 * @property Collection|User[]|null             $users    The ids and User objects.
 * @property object[]|null                      $members  The ids and partial Member objects.
 * @property object[]|null                      $roles    The ids and Role objects.
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
     * @inheritdoc
     */
    protected function afterConstruct(): void
    {
        foreach ($this->attributes['users'] ?? [] as $snowflake => $user) {
            if ($userPart = $this->discord->users->get('id', $snowflake)) {
                $userPart->fill((array) $user);
            } else {
                $userPart = $this->factory->create(User::class, $user, true);
                $this->discord->users->pushItem($userPart);
            }
        }
    }

    /**
     * Returns a collection of resolved users.
     *
     * @return Collection|User[] Map of Snowflakes to user objects
     */
    protected function getUsersAttribute(): Collection
    {
        $collection = Collection::for(User::class);

        foreach ($this->attributes['users'] ?? [] as $snowflake => $user) {
            if (! $userPart = $this->discord->users->get('id', $snowflake)) {
                $userPart = $this->factory->create(User::class, $user, true);
            }

            $collection->push($userPart);
        }

        return $collection;
    }

    /**
     * Returns a collection of resolved channels.
     *
     * Partial Channel objects only have id, name, type and permissions fields. Threads will also have thread_metadata and parent_id fields.
     *
     * @return Collection|Channel[]|Thread[] Map of Snowflakes to partial channel objects
     */
    protected function getChannelsAttribute(): Collection
    {
        $collection = new Collection();

        foreach ($this->attributes['channels'] ?? [] as $snowflake => $channel) {
            if (! $channelPart = $this->discord->getChannel($snowflake)) {
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
     * @return Collection|Message[] Map of Snowflakes to partial messages objects
     */
    protected function getMessagesAttribute(): Collection
    {
        $collection = Collection::for(Message::class);

        foreach ($this->attributes['messages'] ?? [] as $snowflake => $message) {
            if ($channelPart = $this->discord->getChannel($message->channel_id)) {
                if (! $messagePart = $channelPart->messages[$snowflake]) {
                    $messagePart = $this->factory->create(Message::class, $message, true);
                }
            } else {
                $messagePart = $this->factory->create(Message::class, $message, true);
            }

            $collection->push($messagePart);
        }

        return $collection;
    }
}
