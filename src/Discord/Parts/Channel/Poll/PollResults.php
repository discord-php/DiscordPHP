<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel\Poll;

use Carbon\Carbon;
use Discord\Parts\Part;

/**
 * The current results of a poll.
 *
 * @link https://discord.com/developers/docs/resources/poll#poll-results-object
 *
 * @since 10.0.0
 *
 * @property boolean            $is_finalized   Whether the votes have been precisely counted
 * @property PollAnswerCount[]  $answer_counts  The counts for each answer
 */
class PollResults extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'is_finalized',
        'answer_counts',
    ];

    /**
     * Returns the answer counts attribute.
     *
     * @return PollAnswerCount|null
     */
    protected function getAnswerCountsAttribute(): ?PollAnswerCount
    {
        if (! isset($this->attributes['answer_counts'])) {
            return null;
        }

        return $this->factory->part(PollAnswerCount::class, (array) $this->attributes['answer_counts'], true);
    }
}
