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

use Discord\Parts\Invite\Invite;
use Discord\Parts\User\Connection;

final class OpenApiSchemaChangesTest extends DiscordTestCase
{
    public function testInviteApplicationRetainsConjureProjectId(): void
    {
        $factory = getMockDiscord()->getFactory();
        $invite = $factory->part(Invite::class, ['target_application' => ['id' => '1', 'name' => 'App', 'conjure_project_id' => '2']], true);

        $this->assertSame('2', $invite->target_application->conjure_project_id);
        $this->assertNull($factory->part(Invite::class, ['target_application' => ['id' => '1']], true)->target_application->conjure_project_id);
    }

    public function testLetterboxdConnectionNeedsNoEnumChange(): void
    {
        $connection = getMockDiscord()->getFactory()->part(Connection::class, ['id' => '1', 'name' => 'Reviewer', 'type' => 'letterboxd'], true);

        $this->assertSame('letterboxd', $connection->type);
        $this->assertSame('letterboxd', $connection->jsonSerialize()['type']);
    }
}
