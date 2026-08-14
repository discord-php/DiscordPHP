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

use Discord\Discord;
use Discord\Parts\Gateway\UpdatePresence;
use Discord\Parts\User\Activity;

final class UpdatePresenceTest extends DiscordTestCase
{
    public function testJsonSerializeBuildsGatewayPresencePayload()
    {
        return wait(function (Discord $discord, $resolve) {
            $activity = $discord->factory->part(Activity::class, [
                'name' => 'Save the Oxford Comma',
                'type' => 0,
            ]);

            $data = [
                'since' => time() * 1000,
                'activities' => [$activity],
                'status' => 'online',
                'afk' => false,
            ];

            $presence = new UpdatePresence($discord, $data);

            $this->assertSame($data, $presence->jsonSerialize());
        }, 10);
    }
}
