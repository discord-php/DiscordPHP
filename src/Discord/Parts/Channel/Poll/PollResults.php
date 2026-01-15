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

namespace Discord\Parts\Channel\Poll;

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * The current results of a poll.
 *
 * @link https://discord.com/developers/docs/resources/poll#poll-results-object
 *
 * @since 10.0.0
 *
 * @property bool                                                     $is_finalized  Whether the votes have been precisely counted
 * @property ExCollectionInterface<PollAnswerCount>|PollAnswerCount[] $answer_counts The counts for each answer
 */
class PollResults extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'is_finalized',
        'answer_counts',
    ];

    /**
     * Returns the answer counts attribute.
     *
     * @return ExCollectionInterface<PollAnswerCount>|PollAnswerCount[] A collection of poll answer counts.
     */
    protected function getAnswerCountsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('answer_counts', PollAnswerCount::class);
    }
}
