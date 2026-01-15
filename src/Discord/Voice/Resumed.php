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

namespace Discord\Voice;

use Discord\Parts\Part;

/**
 * Represents the resumed data.
 *
 * No date is actually sent with this payload, but having a Part makes it easier to log and handle.
 *
 * @link https://discord.com/developers/docs/topics/voice-connections#resuming-voice-connection-example-resumed-payload
 *
 * @since 10.41.0
 */
class Resumed extends Part
{
}
