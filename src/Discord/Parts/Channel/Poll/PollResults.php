<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Carbon\Carbon;
//use Discord\Builders\PartBuilder;
use Discord\Parts\Part;

 /**
 * Each of the answers available in the poll. The answer_id is a number that labels each answer. As an implementation detail, it currently starts at 1 for the first answer and goes up sequentially. We recommend against depending on this sequence. Currently, there is a maximum of 10 answers per poll.
 *
 * @link https://discord.com/developers/docs/resources/poll#poll-results-object
 *
 *
 *
 * @property boolean        $is_finalized  	  Whether the votes have been precisely counted
 * @property PollAnswerCount[]   $answer_counts    The counts for each answer
 */
class PollResults extends Part
{

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'answer_id',
        'poll_media'
    ];

    // TODO
}
