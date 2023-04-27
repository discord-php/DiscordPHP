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
 * An object that represents a tag that is able to be applied to a thread in a
 * `GUILD_FORUM` channel.
 * At most one of `emoji_id` and `emoji_name` may be set to a non-null value.
 *
 * @link https://discord.com/developers/docs/resources/channel#forum-tag-object
 *
 * @since 7.4.0
 *
 * @property string      $id         The id of the tag.
 * @property string      $name       The name of the tag (0-20 characters).
 * @property bool        $moderated  Whether this tag can only be added to or removed from threads by a member with the `MANAGE_THREADS` permission.
 * @property string|null $emoji_id   The id of a guild's custom emoji.
 * @property string|null $emoji_name The unicode character of the emoji.
 */
class Tag extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'name',
        'moderated',
        'emoji_id',
        'emoji_name',
    ];
}
