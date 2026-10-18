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
use Discord\Parts\Application\Application;

final class ApplicationTest extends DiscordTestCase
{
    public function testConjureProjectIdIsKept()
    {
        return wait(function (Discord $discord, $resolve) {
            $factory = $discord->getFactory();

            $application = $factory->part(Application::class, ['id' => '1', 'name' => 'App', 'conjure_project_id' => '2'], true);
            $this->assertSame('2', $application->conjure_project_id);

            $this->assertNull($factory->part(Application::class, ['id' => '1', 'name' => 'App'], true)->conjure_project_id);

            $resolve();
        });
    }
}
