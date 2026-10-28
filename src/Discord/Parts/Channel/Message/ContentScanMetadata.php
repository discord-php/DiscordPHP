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
 * Discord's response-only content scanning result for an unfurled media item.
 *
 * Absence does not establish that the media is safe; results may arrive in a later message update.
 *
 * @link https://docs.discord.com/developers/components/reference#unfurled-media-item-content-scan-metadata-structure
 *
 * @property-read int $flags Content scan metadata flags, distinct from attachment and embed flags.
 */
class ContentScanMetadata extends Part
{
    /** Discord flagged the media as explicit. */
    public const FLAG_IS_EXPLICIT_CONTENT = 1;
    /** Discord flagged the media as gore. */
    public const FLAG_IS_GORE_CONTENT = 1 << 1;
    /** Discord flagged the media as self-harm. */
    public const FLAG_IS_SELF_HARM_CONTENT = 1 << 2;

    /** @inheritDoc */
    protected $fillable = ['flags'];
}
