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

namespace Discord\Parts\Channel\Message\Embed;

/**
 * A display-only component link preview received from Discord.
 *
 * Author website previews with ComponentEmbedBuilder; bots cannot send this embed type.
 *
 * @link https://docs.discord.com/developers/link-previews/component-embeds
 */
class EmbedComponents extends Embed
{
    public const TYPE = self::TYPE_COMPONENTS;
}
