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

namespace Discord\Parts\Thread;

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Message;
use Discord\Parts\Part;
use Discord\Parts\Thread\Member as ThreadMember;

/**
 * Container for thread search responses.
 *
 * @since 10.47.0
 *
 * @property-read ExCollectionInterface<Thread>       $threads        Collection of threads matching the search.
 * @property-read ExCollectionInterface<ThreadMember> $members        Collection of thread members matching the search. These are not guaranteed to be complete and should be used for informational purposes only.
 * @property-read bool                                $has_more       Whether there are more results available beyond the current page.
 * @property-read ExCollectionInterface<Message>      $first_messages Collection of the first message in each thread returned in the search results. This is not guaranteed to be complete and should be used for informational purposes only.
 * @property-read int                                 $total_results  The total number of threads that matched the search query. This may be an estimate and is not guaranteed to be accurate.
 */
class ThreadSearch extends Part
{
    /** @inheritDoc */
    protected $fillable = [
        'threads',
        'members',
        'has_more',
        'first_messages',
        'total_results',
    ];

    /**
     * Returns the threads as a collection of `Thread` parts.
     *
     * @return ExCollectionInterface<Thread>
     */
    protected function getThreadsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('threads', Thread::class, 'id');
    }

    /**
     * Returns the members as a collection of thread `Member` parts.
     *
     * @return ExCollectionInterface<ThreadMember>
     */
    protected function getMembersAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('members', ThreadMember::class, 'user_id');
    }

    /**
     * Returns the first messages as a collection of `Message` parts.
     *
     * @return ExCollectionInterface<Message>
     */
    protected function getFirstMessagesAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('first_messages', Message::class);
    }
}
