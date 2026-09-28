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

use Discord\Helpers\RegisteredCommand;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Application\Command\Option as CommandOption;
use Discord\Parts\Interactions\Request\Option;
use Discord\WebSockets\Events\InteractionCreate;

/**
 * The autocomplete callback is handed the focused option, so it need not search the interaction for it.
 */
final class RegisteredCommandTest extends DiscordTestCase
{
    public function testTheAutocompleteCallbackReceivesTheFocusedOption()
    {
        $discord = getMockDiscord();
        $option = $discord->getFactory()->part(Option::class, ['name' => 'query', 'value' => 'ab', 'focused' => true], true);
        $received = [];
        $command = new RegisteredCommand($discord, 'search', null, function (...$arguments) use (&$received) {
            $received = $arguments;
        });

        $this->assertTrue($command->suggest($this->interaction(), $option), 'the callback ran');
        $this->assertCount(2, $received);
        $this->assertSame($option, $received[1]);
    }

    public function testSuggestStillWorksWithoutAnOption()
    {
        $received = null;
        $command = new RegisteredCommand(getMockDiscord(), 'search', null, function (...$arguments) use (&$received) {
            $received = $arguments;
        });

        $command->suggest($this->interaction());

        $this->assertNull($received[1]);
    }

    public function testTheFocusedOptionOfASubCommandIsFound()
    {
        $discord = getMockDiscord();
        $factory = $discord->getFactory();
        $focused = $factory->part(Option::class, ['name' => 'query', 'value' => 'ab', 'focused' => true], true);
        $sub = $factory->part(Option::class, ['name' => 'user', 'type' => CommandOption::SUB_COMMAND, 'options' => [(array) $focused->getRawAttributes()]], true);

        $received = null;
        $command = new RegisteredCommand($discord, 'search');
        $command->addSubCommand('user', null, function ($interaction, $option) use (&$received) {
            $received = $option;
        });

        $check = new ReflectionMethod(InteractionCreate::class, 'checkCommand');
        $check->invoke(new InteractionCreate($discord), $command, [$sub], $this->interaction());

        $this->assertInstanceOf(Option::class, $received);
        $this->assertSame('query', $received->name);
        $this->assertSame('ab', $received->value);
    }

    public function testACommandWithoutOptionsIsNotAnError()
    {
        $check = new ReflectionMethod(InteractionCreate::class, 'checkCommand');

        $this->assertFalse($check->invoke(new InteractionCreate(getMockDiscord()), new RegisteredCommand(getMockDiscord(), 'search'), [], $this->interaction()));
    }

    private function interaction(): Interaction
    {
        return $this->createStub(Interaction::class);
    }
}
