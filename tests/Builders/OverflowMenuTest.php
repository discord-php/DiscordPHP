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

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\Container;
use Discord\Builders\Components\Label;
use Discord\Builders\Components\MenuActionItem;
use Discord\Builders\Components\OverflowMenu;
use Discord\Builders\Components\OverflowMenuGroup;
use Discord\Builders\Components\Section;
use Discord\Builders\Components\StringSelect;
use Discord\Builders\Components\TextDisplay;
use Discord\Builders\MessageBuilder;
use Discord\Builders\ModalBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message\MenuActionItem as MenuActionItemPart;
use Discord\Parts\Channel\Message\Message;
use Discord\Parts\Channel\Message\OverflowMenu as OverflowMenuPart;
use Discord\Parts\Channel\Message\OverflowMenuGroup as OverflowMenuGroupPart;

final class OverflowMenuTest extends DiscordTestCase
{
    public function testRowPayloadPreservesGroupOrderAndNumericIds(): void
    {
        $row = ActionRow::new()
            ->addComponent(Button::new(Button::STYLE_PRIMARY)->setLabel('Open'))
            ->addComponent(self::menu());

        $body = self::encode(MessageBuilder::new()->addComponent($row));
        $this->assertSame(2, $body['components'][0]['components'][0]['type']);
        $this->assertSame(self::expectedMenu(), $body['components'][0]['components'][1]);
    }

    public function testSendMessageUsesPreviewWireShapeThroughRecordedTransport()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $body = MessageBuilder::new()->setIsComponentsV2Flag()
                ->addComponent(ActionRow::new()->addComponent(self::menu()));
            $response = self::encode($body) + ['id' => '100', 'channel_id' => '20'];
            $driver = getMockHttpDriver(fn () => $response);
            $mock->getHttpClient()->setDriver($driver);
            $channel = $mock->getFactory()->part(Channel::class, ['id' => '20', 'type' => Channel::TYPE_DM], true);

            $discord->getLoop()->futureTick(function () use ($channel, $body, $driver, $resolve) {
                $channel->sendMessage($body)->then(function (Message $message) use ($driver) {
                    $request = $driver->requests[0];
                    $this->assertSame('POST', $request['method']);
                    $this->assertStringEndsWith('/channels/20/messages', $request['url']);
                    $this->assertSame(1, preg_match('/name=payload_json\r?\nContent-Type: application\/json\r?\n\r?\n([^\r\n]+)/', $request['raw'], $matches));
                    $payload = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
                    $this->assertSame(self::expectedMenu(), $payload['components'][0]['components'][0]);
                    $menu = $message->components->first()->components->first();
                    $this->assertInstanceOf(OverflowMenuPart::class, $menu);
                    $this->assertInstanceOf(MenuActionItemPart::class, $menu->groups[0]->components[0]);
                })->then($resolve, $resolve);
            });
        });
    }

    public function testSectionPayloadHydratesTypedPartsAndRoundTrips(): void
    {
        $section = Section::new()->addComponent('Actions')->setAccessory(self::menu());
        $messageBody = MessageBuilder::new()->addComponent($section);
        $body = self::encode($messageBody);
        $this->assertSame(Message::FLAG_IS_COMPONENTS_V2, $body['flags']);
        $this->assertSame(self::expectedMenu(), $body['components'][0]['accessory']);

        $factory = getMockDiscord()->getFactory();
        $message = $factory->part(Message::class, (array) json_decode(json_encode($body, JSON_THROW_ON_ERROR)), true);
        $part = $message->components->first();
        $menu = $part->accessory;
        $this->assertInstanceOf(OverflowMenuPart::class, $menu);
        $this->assertSame($menu, $part->accessory);
        $this->assertSame(25, $menu->type);
        $this->assertInstanceOf(OverflowMenuGroupPart::class, $menu->groups[0]);
        $this->assertInstanceOf(MenuActionItemPart::class, $menu->groups[0]->components[0]);
        $this->assertSame('⚠️', $menu->groups[0]->components[1]->emoji->name);
        $this->assertNull($menu->groups[1]->label);
        $this->assertSame(self::expectedMenu(), self::encode(OverflowMenu::fromPart($menu)));
        $this->assertSame($messageBody->countTotalComponents(), MessageBuilder::fromPart($message)->countTotalComponents());
    }

    public function testRowNestedInContainerCountsFortyThenRejectsMutationToFortyOne(): void
    {
        $group = self::group(37);
        $container = Container::new()->addComponent(OverflowMenu::new()->addGroup($group));
        $message = MessageBuilder::new()->addComponent($container);

        $this->assertSame(40, $message->countTotalComponents());
        $this->assertSame(40, $message->countTotalComponents(self::encode($message)['components']));
        $this->assertCount(37, self::encode($message)['components'][0]['components'][0]['components'][0]['groups'][0]['components']);

        $group->addComponent(MenuActionItem::new('Too late'));
        $this->expectException(OverflowException::class);
        self::encode($message);
    }

    public function testSectionAccessoryCountsFortyThenRejectsNestedCandidate(): void
    {
        $section = Section::new()->addComponent('Actions')
            ->setAccessory(OverflowMenu::new()->addGroup(self::group(36)));
        $message = MessageBuilder::new()->addComponent(TextDisplay::new('Intro'))->addComponent($section);
        $this->assertSame(40, $message->countTotalComponents());
        $this->assertCount(2, self::encode($message)['components']);

        $this->expectException(OverflowException::class);
        $message->addComponent(TextDisplay::new('Too many'));
    }

    public function testOversizedSectionIsRejectedWhenFirstAdded(): void
    {
        $section = Section::new()->addComponent('Actions')
            ->setAccessory(OverflowMenu::new()->addGroup(self::group(38)));
        $this->expectException(OverflowException::class);
        MessageBuilder::new()->addComponent($section);
    }

    public function testActionCannotBePlacedDirectlyInRow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ActionRow::new()->addComponent(MenuActionItem::new('Invalid'));
    }

    public function testMenuCannotShareRowWithASelect(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ActionRow::new()->addComponent(StringSelect::new())->addComponent(self::menu());
    }

    public function testSelectCannotBeAddedToAMenuRow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ActionRow::new()->addComponent(self::menu())->addComponent(StringSelect::new());
    }

    public function testMenuCannotBeInsideModalLabel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Label::new('Invalid', self::menu());
    }

    public function testMenuCannotBeInsideModalRow(): void
    {
        $row = ActionRow::new()->addComponent(self::menu());
        $this->expectException(InvalidArgumentException::class);
        ModalBuilder::new('Not a message', 'invalid', [$row]);
    }

    public function testMenuCannotBeAddedAfterModalHasBeenComposed(): void
    {
        $row = ActionRow::new();
        $modal = ModalBuilder::new('Not a message', 'invalid', [$row]);
        $row->addComponent(self::menu());
        $this->expectException(InvalidArgumentException::class);
        self::encode($modal);
    }

    public function testEmptyMenuIsRejected(): void
    {
        $this->expectException(DomainException::class);
        self::encode(OverflowMenu::new());
    }

    public function testEmptyGroupIsRejected(): void
    {
        $this->expectException(DomainException::class);
        self::encode(OverflowMenu::new()->addGroup(OverflowMenuGroup::new()));
    }

    public function testUnknownStyleIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MenuActionItem::new('Not defined', 4);
    }

    public function testLinkWithoutUrlIsRejected(): void
    {
        $this->expectException(DomainException::class);
        self::encode(MenuActionItem::new('A link', MenuActionItem::STYLE_LINK));
    }

    public function testLegacyBuilderCanWrapAnOverflowMenuWithoutDroppingContent(): void
    {
        $message = MessageBuilder::new()->setContent('Hello')->addComponent(self::menu());
        $body = self::encode($message);
        $this->assertSame('Hello', $body['content']);
        $this->assertSame(1, $body['components'][0]['type']);
        $this->assertSame(self::expectedMenu(), $body['components'][0]['components'][0]);
    }

    private static function menu(): OverflowMenu
    {
        return OverflowMenu::new()->setId(13)
            ->addGroup(OverflowMenuGroup::new('General')
                ->addComponent(MenuActionItem::new('View')->setId(2))
                ->addComponent(MenuActionItem::new('Archive', MenuActionItem::STYLE_DANGER)
                    ->setDescription('Hide this entry')
                    ->setEmoji('⚠️')))
            ->addGroup(OverflowMenuGroup::new()
                ->addComponent(MenuActionItem::new('Docs', MenuActionItem::STYLE_LINK)
                    ->setUrl('https://example.com/docs')));
    }

    private static function group(int $count): OverflowMenuGroup
    {
        $group = OverflowMenuGroup::new();
        for ($i = 1; $i <= $count; ++$i) {
            $group->addComponent(MenuActionItem::new('Action '.$i)->setId(100 + $i));
        }

        return $group;
    }

    private static function expectedMenu(): array
    {
        return [
            'type' => 25,
            'groups' => [
                ['components' => [
                    ['type' => 26, 'label' => 'View', 'style' => 1, 'id' => 2],
                    ['type' => 26, 'label' => 'Archive', 'style' => 2, 'description' => 'Hide this entry', 'emoji' => ['name' => '⚠️']],
                ], 'label' => 'General'],
                ['components' => [
                    ['type' => 26, 'label' => 'Docs', 'style' => 3, 'url' => 'https://example.com/docs'],
                ]],
            ],
            'id' => 13,
        ];
    }

    private static function encode(JsonSerializable $builder): array
    {
        return json_decode(json_encode($builder, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
