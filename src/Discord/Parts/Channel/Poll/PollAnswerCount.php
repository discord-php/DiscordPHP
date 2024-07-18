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

use Discord\Parts\Part;

/**
 * The number of votes for an answer in a poll.
 *
 * @link https://discord.com/developers/docs/resources/poll#poll-results-object-poll-answer-count-object-structure
 *
 * @since 10.0.0
 *
 * @property int    $id         The answer_id
 * @property int    $count      The number of votes for this answer
 * @property bool   $me_voted 	Whether the current user voted for this answer
 */
class PollAnswerCount extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'count',
        'me_voted',
    ];
}
