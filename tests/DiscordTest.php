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

it('has required Discord environment variables', function () {
    expect(getenv('DISCORD_TOKEN'))->not->toBeFalse('Discord token is missing');
    expect(getenv('TEST_CHANNEL'))->not->toBeFalse('Test channel ID is missing');
    expect(getenv('TEST_CHANNEL_NAME'))->not->toBeFalse('Test channel name is missing');
});

