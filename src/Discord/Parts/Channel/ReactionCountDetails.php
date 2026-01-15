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

namespace Discord\Parts\Channel;

use Discord\Parts\Part;

/**
 * The reaction count details object contains a breakdown of normal and super reaction counts for the associated emoji.
 *
 * @link https://discord.com/developers/docs/resources/message#reaction-count-details-object
 *
 * @since 10.36.29
 *
 * @property int $burst  Count of super reactions.
 * @property int $normal Count of normal reactions.
 */
class ReactionCountDetails extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'burst',
        'normal',
    ];
}
