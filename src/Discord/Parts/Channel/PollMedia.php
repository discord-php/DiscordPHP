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
 * The poll media object is a common object that backs both the question and answers. The intention is that it allows us to extensibly add new ways to display things in the future. For now, question only supports text, while answers can have an optional emoji.
 *
 * @link https://discord.com/developers/docs/resources/poll#poll-media-object
 *
 *
 *
 * @property string|null        $text   The text of the field. Text should always be non-null for both questions and answers, but please do not depend on that in the future. The maximum length of text is 300 for the question, and 55 for any answer.
 * @property Emoji|string|null  $emoji  The emoji of the field. When creating a poll answer with an emoji, one only needs to send either the id (custom emoji) or name (default emoji) as the only field.
 */
class PollMedia extends Part
{

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'text',
        'emoji'
    ];

    // TODO
}
