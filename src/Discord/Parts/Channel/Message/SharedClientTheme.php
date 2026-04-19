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

namespace Discord\Parts\Channel\Message;

use Discord\Parts\Part;

/**
 * The shared client theme object sent with a message.
 *
 * @link https://docs.discord.com/developers/resources/message#shared-client-theme-object
 *
 * @since 10.47.11
 *
 * @property string[]    $colors         The hexadecimal-encoded colors of the theme (max of 5).
 * @property int         $gradient_angle The direction of the theme's colors (max of 360).
 * @property int         $base_mix       The intensity of the theme's colors (max of 100).
 * @property string|null $base_theme     The mode of the theme.
 */
class SharedClientTheme extends Part
{
    public const TYPE_BASE_THEME_UNSET = 0;
    public const TYPE_BASE_THEME_DARK = 1;
    public const TYPE_BASE_THEME_LIGHT = 2;
    public const TYPE_BASE_THEME_DARKER = 3;
    public const TYPE_BASE_THEME_MIDNIGHT = 4;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'colors',
        'gradient_angle',
        'base_mix',
        'base_theme',
    ];
}
