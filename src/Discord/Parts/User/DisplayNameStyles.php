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

namespace Discord\Parts\User;

use Discord\Parts\Part;

/**
 * The data for the user's display name styles.
 *
 * @link TODO
 *
 * @property string $font_id   The font ID used in the display name style.
 * @property string $effect_id The effect ID used in the display name style.
 * @property int[]  $colors    An array of colors used in the display name style.
 */
class DisplayNameStyles extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'font_id',
        'effect_id',
        'colors',
    ];
}
