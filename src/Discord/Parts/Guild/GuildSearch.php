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

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Message;
use Discord\Parts\Part;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\Member;

/**
 * Represents a Guild Search result.
 *
 * @link https://discord.com/developers/docs/resources/guild#search-guild-members
 *
 * @property string                                   $analytics_id                The analytics ID for the search query.
 * @property ExCollectionInterface<Message>|Message[] $messages                    An array of messages that match the query.
 * @property bool                                     $doing_deep_historical_index The status of the guild's deep historical indexing operation, if any.
 * @property int                                      $total_results               The total number of results that match the query.
 * @property ExCollectionInterface<Thread>|Thread[]   $threads                     The threads that contain the returned messages.
 * @property ExCollectionInterface<Member>|Member[]   $members                     A thread member object for each returned thread the current user has joined.
 * @property ?int|null                                $documents_indexed           The number of documents that have been indexed during the current index operation, if any.
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
     * The nested array was used to provide surrounding context to search results. However, surrounding context is no longer returned.
     *
     * @return ExCollectionInterface<Message>|Message[]
     */
    protected function getMessagesAttribute(): ExCollectionInterface
    {
        /** @var ExCollectionInterface<Message> $collection */
        $collection = $this->discord->getCollectionClass()::for(Message::class);

        if (! isset($this->attributes['messages'])) {
            return $collection;
        }

        foreach ($this->attributes['messages'] as $snowflake => &$message) {
            if ($guild = $this->discord->guilds->get('id', $message->guild_id)) {
                if ($channel = $guild->channels->get('id', $message->channel_id)) {
                    $message = $messagePart = $channel->messages->get('id', $snowflake);
                }
            }

            $collection->pushItem($messagePart ?? $message = $this->factory->part(Message::class, (array) $message, true));
        }

        return $collection;
    }

    /**
     * Returns a collection of members found in the search.
     *
     * @return ExCollectionInterface<Member>|Member[]
     */
    protected function getMembersAttribute(): ExCollectionInterface
    {
        /** @var ExCollectionInterface<Member> $collection */
        $collection = $this->discord->getCollectionClass()::for(Member::class);

        if (! isset($this->attributes['members'])) {
            return $collection;
        }

        foreach ($this->attributes['members'] ?? [] as $snowflake => &$member) {
            if ($guild_id = $member->guild_id) {
                if ($guild = $this->discord->guilds->get('id', $guild_id)) {
                    $member = $memberPart = $guild->members->get('id', $snowflake);
                }
            }

            if (! isset($memberPart)) {
                $member->user = $this->attributes['users']->$snowflake;
                $member = $memberPart = $this->factory->part(Member::class, (array) $member, true);
            }

            $collection->pushItem($memberPart);
        }

        return $collection;
    }

    /**
     * Returns a collection of threads found in the search.
     *
     * @return ExCollectionInterface<Thread>|Thread[]
     */
    protected function getThreadsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('threads', Thread::class);
    }
}
