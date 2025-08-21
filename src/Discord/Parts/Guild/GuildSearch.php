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

namespace Discord\Parts\Guild;

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Message;
use Discord\Parts\Part;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\Member;

/**
 * TODO.
 *
 * @link TODO
 *
 * @property string                                 $analytics_id
 * @property ExCollectionInterface<Message>         $messages
 * @property bool                                   $doing_deep_historical_index
 * @property int                                    $total_results
 * @property ExCollectionInterface<Thread>|Thread[] $threads
 * @property ExCollectionInterface<Member>|Member[] $members
 * @property ?int|null                              $documents_indexed
 */
class GuildSearch extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'analytics_id',
        'messages',
        'doing_deep_historical_index',
        'total_results',
        'threads',
        'members',
        'documents_indexed',
    ];

    /**
     * Returns a collection of messages found in the search.
     *
     * @return ExCollectionInterface|Message[]|null
     */
    protected function getMessagesAttribute(): ?ExCollectionInterface
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

            $collection->pushItem($messagePart ?? $this->factory->part(Message::class, (array) $message + ['guild_id' => $this->guild_id], true));
        }

        return $collection;
    }

    /**
     * Returns a collection of members found in the search.
     *
     * @return ExCollectionInterface|Member[]|null
     */
    protected function getMembersAttribute(): ?ExCollectionInterface
    {
        $collection = Collection::for(Member::class);

        foreach ($this->attributes['members'] ?? [] as $snowflake => $member) {
            if ($guild_id = $member->guild_id ?? null) {
                if ($guild = $this->discord->guilds->get('id', $guild_id)) {
                    $memberPart = $guild->members->get('id', $snowflake);
                }
            }

            if (! isset($memberPart)) {
                $member->user = $this->attributes['users']->$snowflake;
                $memberPart = $this->factory->part(Member::class, (array) $member + ['guild_id' => $this->guild_id], true);
            }

            $collection->pushItem($memberPart);
        }

        return $collection;
    }

    /**
     * Returns a collection of threads found in the search.
     *
     * @return ExCollectionInterface|Thread[]
     */
    protected function getThreadsAttribute(): ExCollectionInterface
    {
        $collection = Collection::for(Thread::class);

        foreach ($this->attributes['threads'] ?? [] as $thread) {
            $collection->pushItem($this->factory->part(Thread::class, (array) $thread, true));
        }

        return $collection;
    }
}
