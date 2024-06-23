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
 * The poll object has a lot of levels and nested structures. It was also designed to support future extensibility, so some fields may appear to be more complex than necessary.
 * This is the request object used when creating a poll across the different endpoints. It is similar but not exactly identical to the main poll object. The main difference is that the request has duration which eventually becomes expiry.
 *
 * @link https://discord.com/developers/docs/resources/poll#poll-create-request-object-poll-create-request-object-structure
 *
 *
 *
 * @property PollMedia|string   $question           The question of the poll. Only text is supported.
 * @property PollAnswer[]	    $answers            Each of the answers available in the poll, up to 10.
 * @property int                $duration	        Number of hours the poll should be open for, up to 7 days.
 * @property bool               $allow_multiselect	Whether a user can select multiple answers.
 * @property int|null           $layout_type?	    The layout type of the poll. Defaults to... DEFAULT!
 */
class PollRequest extends Part
{
    public const LAYOUT_DEFAULT = 1; // The, uhm, default layout type.

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'question',
        'answers',
        'duration',
        'allow_multiselect',
        'layout_type'
    ];

    // TODO
}
