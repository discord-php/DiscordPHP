<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel\Forum;

use Discord\Parts\Part;

/**
 * An object that specifies the emoji to use as the default way to react to a
 * forum post. Exactly one of `emoji_id` and `emoji_name` must be set.
 *
 * @link https://discord.com/developers/docs/resources/channel#default-reaction-object
 *
 * @since 7.4.0
 *
 * @property ?string $emoji_id   The id of a guild's custom emoji.
 * @property ?string $emoji_name The unicode character of the emoji.
 */
class Reaction extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'emoji_id',
        'emoji_name',
    ];
}
