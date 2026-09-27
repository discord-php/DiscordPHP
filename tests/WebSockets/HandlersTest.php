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
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Parts\Guild\ScheduledEventException;
use Discord\WebSockets\Event;
use Discord\WebSockets\Events\GuildScheduledEventExceptionCreate;
use Discord\WebSockets\Events\GuildScheduledEventExceptionDelete;
use Discord\WebSockets\Events\GuildScheduledEventExceptionUpdate;
use Discord\WebSockets\Handlers;

use function Discord\promiseFromGenerator;

/**
 * The registry that picks the class to handle each dispatch. An event class it does not list is
 * never run: the dispatch is dropped without a word.
 */
final class HandlersTest extends DiscordTestCase
{
    /**
     * Event classes left out of the registry on purpose. Registering one means taking it off this list.
     */
    private const UNREGISTERED = [
        // Discord has not documented GAME_SERVER_UPDATE or GAME_SERVER_DELETE, and both classes are marked TBD.
        'GameServerDelete',
        'GameServerUpdate',
    ];

    public function testEveryEventClassIsRegistered()
    {
        $registered = array_column((new Handlers())->getHandlers(), 'class');
        $folder = dirname((new ReflectionClass(Event::class))->getFileName()).'/Events';

        foreach (glob($folder.'/*.php') as $file) {
            $name = basename($file, '.php');
            $class = 'Discord\\WebSockets\\Events\\'.$name;

            if (in_array($name, self::UNREGISTERED, true)) {
                $this->assertNotContains($class, $registered, "{$name} is registered now: take it off UNREGISTERED");
            } else {
                $this->assertContains($class, $registered, "{$name} handles an event, but Handlers never runs it");
            }
        }
    }

    public function testTheScheduledEventExceptionEventsHaveHandlers()
    {
        $handlers = new Handlers();

        foreach ([
            Event::GUILD_SCHEDULED_EVENT_EXCEPTION_CREATE => GuildScheduledEventExceptionCreate::class,
            Event::GUILD_SCHEDULED_EVENT_EXCEPTION_UPDATE => GuildScheduledEventExceptionUpdate::class,
            Event::GUILD_SCHEDULED_EVENT_EXCEPTION_DELETE => GuildScheduledEventExceptionDelete::class,
        ] as $event => $class) {
            $this->assertSame($class, $handlers->getHandler($event)['class'] ?? null, $event);
        }
    }

    public function testAnExceptionIsKeptOnItsEventUntilItIsDeleted()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $factory = $mock->getFactory();

            /** @var Guild */
            $guild = $factory->part(Guild::class, ['id' => '10'], true);
            $mock->guilds->pushItem($guild);
            /** @var ScheduledEvent */
            $event = $factory->part(ScheduledEvent::class, ['id' => '20', 'guild_id' => '10', 'name' => 'Weekly'], true);
            $guild->guild_scheduled_events->pushItem($event);

            $payload = ['guild_id' => '10', 'event_id' => '20', 'event_exception_id' => '30', 'is_canceled' => false];

            promiseFromGenerator((new GuildScheduledEventExceptionCreate($mock))->handle((object) $payload))
                ->then(function (ScheduledEventException $created) use ($mock, $event, $payload) {
                    $this->assertSame('30', $created->event_exception_id);
                    $this->assertSame(['30'], $this->exceptionIds($event), 'the new exception is kept on its event');

                    return promiseFromGenerator((new GuildScheduledEventExceptionUpdate($mock))->handle((object) (['is_canceled' => true] + $payload)));
                })
                ->then(function (array $updated) use ($mock, $event, $payload) {
                    [$new, $old] = $updated;
                    $this->assertTrue($new->is_canceled);
                    $this->assertFalse($old->is_canceled, 'the listener also gets the exception as it was');
                    $this->assertSame(['30'], $this->exceptionIds($event), 'an update replaces the exception rather than adding one');

                    return promiseFromGenerator((new GuildScheduledEventExceptionDelete($mock))->handle((object) $payload));
                })
                ->then(function (ScheduledEventException $deleted) use ($event) {
                    $this->assertSame('30', $deleted->event_exception_id);
                    $this->assertFalse($deleted->created);
                    $this->assertSame([], $this->exceptionIds($event), 'a deleted exception is gone from its event');
                })
                ->then($resolve, $resolve);
        });
    }

    /**
     * @return string[] The `event_exception_id` of each exception the event holds.
     */
    private function exceptionIds(ScheduledEvent $event): array
    {
        $ids = [];
        /** @var ScheduledEventException $exception */
        foreach ($event->guild_scheduled_event_exceptions as $exception) {
            $ids[] = $exception->event_exception_id;
        }

        return $ids;
    }
}
