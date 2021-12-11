<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Discord\Parts\Part;

/**
 * A Welcome Channel of a Guild
 *
 * @link https://discord.com/developers/docs/resources/guild#welcome-screen-object-welcome-screen-channel-structure
 *
 * @property string $channel_id  The channel's id.
 * @property string $description The description shown for the channel.
 * @property string $emoji_id    The emoji id, if the emoji is custom.
 * @property string $emoji_name  The emoji name if custom, the unicode character if standard, or null if no emoji is set.
 */
class WelcomeChannel extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['channel_id', 'description', 'emoji_id', 'emoji_name'];
}
