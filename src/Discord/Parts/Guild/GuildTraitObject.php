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

namespace Discord\Parts\Guild;

use Discord\Parts\Part;

/**
 * Guild Trait Object.
 *
 * @link https://discord.com/developers/docs/resources/guild#guild-trait-object
 *
 * @since 10.22.0
 *
 * @property string|null $emoji_id       The id of a guild's custom emoji.
 * @property string|null $emoji_name     The unicode character of the emoji.
 * @property bool|null   $emoji_animated Whether the emoji is animated.
 * @property string|null $label          The label of the trait.
 * @property int|null    $position       The position of the trait.
 */
class GuildTraitObject extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'emoji_id',
        'emoji_name',
        'emoji_animated',
        'label',
        'position',
    ];
}
