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

/**
 * Channel that can only contain threads, similar to GUILD_FORUM channels.
 *
 * The GUILD_MEDIA channel type is still in active development.
 * Avoid implementing any features that are not documented, since they are subject to change without notice!
 */
class GuildMedia extends Channel
{
}
