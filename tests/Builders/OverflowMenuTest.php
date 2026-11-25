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
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message\MenuActionItem as MenuActionItemPart;
use Discord\Parts\Channel\Message\Message;
use Discord\Parts\Channel\Message\OverflowMenu as OverflowMenuPart;
use Discord\Parts\Channel\Message\OverflowMenuGroup as OverflowMenuGroupPart;
use Discord\Parts\Interactions\MessageComponent;
use Discord\WebSockets\Events\InteractionCreate;

final class OverflowMenuTest extends DiscordTestCase
{
    public function testRowPayloadPreservesGroupOrderAndNumericIds(): void
    {
        $row = ActionRow::new()
            ->addComponent(Button::new(Button::STYLE_PRIMARY)->setLabel('Open'))
            ->addComponent(self::menu());

        $body = self::encode(MessageBuilder::new()->addComponent($row));
        $this->assertSame(Message::FLAG_IS_COMPONENTS_V2, $body['flags']);
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
        $this->assertInstanceOf(ExCollectionInterface::class, $menu->groups);
        $this->assertInstanceOf(ExCollectionInterface::class, $menu->groups->first()->components);
        $this->assertSame($menu->groups[0]->components[0], $menu->groups->first()->components->get('id', 2));
        $this->assertInstanceOf(OverflowMenuGroupPart::class, $menu->groups[0]);
        $this->assertInstanceOf(MenuActionItemPart::class, $menu->groups[0]->components[0]);
        $this->assertSame('⚠️', $menu->groups[0]->components[1]->emoji->name);
        $this->assertNull($menu->groups[1]->label);
        $this->assertSame(self::expectedMenu(), self::encode(OverflowMenu::fromPart($menu)));
        $this->assertSame($messageBody->countTotalComponents(), MessageBuilder::fromPart($message)->countTotalComponents());
    }

    public function testOptionalActionIdsDoNotCollideWithPositions(): void
    {
        $group = getMockDiscord()->getFactory()->part(OverflowMenuGroupPart::class, [
            'components' => [
                ['type' => 26, 'label' => 'No ID', 'style' => 1, 'custom_id' => 'no-id'],
                ['type' => 26, 'id' => 0, 'label' => 'Zero ID', 'style' => 1, 'custom_id' => 'zero-id'],
                ['type' => 26, 'id' => 14, 'label' => 'Another', 'style' => 1, 'custom_id' => 'another'],
            ],
        ], true);

        $this->assertSame('No ID', $group->components->first()->label);
        $this->assertSame('Zero ID', $group->components->get('id', 0)->label);
        $this->assertSame('Another', $group->components->get('id', 14)->label);
        $this->assertSame(['No ID', 'Zero ID', 'Another'], array_column(self::encode(OverflowMenuGroup::fromPart($group))['components'], 'label'));
    }

    public function testProtectedActionMutatorsRemainAvailableThroughBuilderProperties(): void
    {
        $item = MenuActionItem::new('Original');
        $item->label = 'Updated';
        $item->description = 'A description';
        $item->id = 0;
        $item->custom_id = 'update';
        $item->disabled = false;

        $this->assertSame('Updated', $item->label);
        $this->assertSame('A description', $item->description);
        $this->assertSame(0, $item->id);
        $this->assertSame('update', $item->custom_id);
        $this->assertSame(0, self::encode($item)['id']);
        $this->assertFalse(self::encode($item)['disabled']);

        $item->id = null;
        $this->assertArrayNotHasKey('id', self::encode($item));
    }

    public function testRowNestedInContainerCountsFortyThenRejectsMutationToFortyOne(): void
    {
        $menu = self::menuWithActions(37);
        $container = Container::new()->addComponent($menu);
        $message = MessageBuilder::new()->addComponent($container);

        $this->assertSame(40, $message->countTotalComponents());
        $this->assertSame(40, $message->countTotalComponents(self::encode($message)['components']));
        $groups = self::encode($message)['components'][0]['components'][0]['components'][0]['groups'];
        $this->assertSame([8, 8, 8, 8, 5], array_map(fn ($group) => count($group['components']), $groups));

        $menu->getGroups()[4]->addComponent(MenuActionItem::new('Too late')->setCustomId('late'));
        $this->expectException(OverflowException::class);
        self::encode($message);
    }

    public function testSectionAccessoryCountsFortyThenRejectsNestedCandidate(): void
    {
        $section = Section::new()->addComponent('Actions')
            ->setAccessory(self::menuWithActions(36));
        $message = MessageBuilder::new()->addComponent(TextDisplay::new('Intro'))->addComponent($section);
        $this->assertSame(40, $message->countTotalComponents());
        $this->assertCount(2, self::encode($message)['components']);

        $this->expectException(OverflowException::class);
        $message->addComponent(TextDisplay::new('Too many'));
    }

    public function testOversizedSectionIsRejectedWhenFirstAdded(): void
    {
        $section = Section::new()->addComponent('Actions')
            ->setAccessory(self::menuWithActions(38));
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

    public function testStandaloneOverflowMenuWrapsInComponentsV2Row(): void
    {
        $message = MessageBuilder::new()->addComponent(self::menu());
        $body = self::encode($message);
        $this->assertSame(Message::FLAG_IS_COMPONENTS_V2, $body['flags']);
        $this->assertSame(1, $body['components'][0]['type']);
        $this->assertSame(self::expectedMenu(), $body['components'][0]['components'][0]);
    }

    public function testLateRowMutationDoesNotSendOverflowMenuInLegacyMessage(): void
    {
        $row = ActionRow::new()->addComponent(Button::new(Button::STYLE_LINK)->setLabel('Existing')->setUrl('https://example.com'));
        $message = MessageBuilder::new()->setContent('Legacy content')->addComponent($row);
        $row->addComponent(self::menu());

        $this->expectException(InvalidArgumentException::class);
        self::encode($message);
    }

    public function testCannotClearV2FlagFromOverflowMenuMessage(): void
    {
        $message = MessageBuilder::new()->addComponent(self::menu())->setIsComponentsV2Flag(false);

        $this->expectException(InvalidArgumentException::class);
        self::encode($message);
    }

    public function testIncomingMenuActionInteractionUsesSelectedItemIds(): void
    {
        $interaction = (new InteractionCreate(getMockDiscord()))->handle((object) [
            'id' => '999',
            'type' => 3,
            'data' => (object) ['component_type' => 26, 'id' => 2, 'custom_id' => 'view'],
        ]);

        $this->assertInstanceOf(MessageComponent::class, $interaction);
        $this->assertSame('999', $interaction->id);
        $this->assertSame(26, $interaction->data->component_type);
        $this->assertSame(2, $interaction->data->id);
        $this->assertSame('view', $interaction->data->custom_id);
    }

    public function testDefaultActionWithoutCustomIdIsRejected(): void
    {
        $this->expectException(DomainException::class);
        self::encode(MenuActionItem::new('No ID'));
    }

    public function testDangerActionWithoutCustomIdIsRejected(): void
    {
        $this->expectException(DomainException::class);
        self::encode(MenuActionItem::new('No ID', MenuActionItem::STYLE_DANGER));
    }

    public function testLinkActionDoesNotAcceptCustomId(): void
    {
        $this->expectException(DomainException::class);
        self::encode(MenuActionItem::new('Link', MenuActionItem::STYLE_LINK)
            ->setUrl('https://example.com')->setCustomId('no-interaction'));
    }

    public function testEmptyCustomIdIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MenuActionItem::new('Empty')->setCustomId('');
    }

    public function testCustomIdOver100CharactersIsRejected(): void
    {
        $this->expectException(LengthException::class);
        MenuActionItem::new('Long ID')->setCustomId(str_repeat('a', 101));
    }

    public function testActionLabelOver80CharactersIsRejected(): void
    {
        $this->expectException(LengthException::class);
        MenuActionItem::new(str_repeat('é', 81));
    }

    public function testGroupLabelOver80CharactersIsRejected(): void
    {
        $this->expectException(LengthException::class);
        OverflowMenuGroup::new(str_repeat('é', 81));
    }

    public function testDescriptionOver100CharactersIsRejected(): void
    {
        $this->expectException(LengthException::class);
        MenuActionItem::new('Description')->setDescription(str_repeat('é', 101));
    }

    public function testDocumentedTextLimitsAcceptExactBoundaries(): void
    {
        $label = str_repeat('é', 80);
        $description = str_repeat('é', 100);
        $customId = str_repeat('é', 100);
        $group = OverflowMenuGroup::new($label)->addComponent(
            MenuActionItem::new($label)->setDescription($description)->setCustomId($customId)
        );
        $body = self::encode($group);

        $this->assertSame($label, $body['label']);
        $this->assertSame($label, $body['components'][0]['label']);
        $this->assertSame($description, $body['components'][0]['description']);
        $this->assertSame($customId, $body['components'][0]['custom_id']);
    }

    public function testNinthGroupIsRejected(): void
    {
        $menu = OverflowMenu::new();
        for ($i = 0; $i < 8; ++$i) {
            $menu->addGroup(OverflowMenuGroup::new());
        }
        $this->assertCount(8, $menu->getGroups());

        $this->expectException(OverflowException::class);
        $menu->addGroup(OverflowMenuGroup::new());
    }

    public function testNinthActionInGroupIsRejected(): void
    {
        $group = OverflowMenuGroup::new();
        for ($i = 0; $i < 8; ++$i) {
            $group->addComponent(MenuActionItem::new('Item '.$i)->setCustomId('item-'.$i));
        }
        $this->assertCount(8, self::encode($group)['components']);

        $this->expectException(OverflowException::class);
        $group->addComponent(MenuActionItem::new('Ninth'));
    }

    protected static function menu(): OverflowMenu
    {
        $menu = OverflowMenu::new();
        $menu->id = 13;
        $view = MenuActionItem::new('View')->setCustomId('view');
        $view->id = '2';

        return $menu->addGroup(OverflowMenuGroup::new('General')
                ->addComponent($view)
                ->addComponent(MenuActionItem::new('Archive', MenuActionItem::STYLE_DANGER)
                    ->setCustomId('archive')
                    ->setDisabled()
                    ->setDescription('Hide this entry')
                    ->setEmoji('⚠️')))
            ->addGroup(OverflowMenuGroup::new()
                ->addComponent(MenuActionItem::new('Docs', MenuActionItem::STYLE_LINK)
                    ->setUrl('https://example.com/docs')));
    }

    protected static function menuWithActions(int $count): OverflowMenu
    {
        $menu = OverflowMenu::new();
        for ($i = 0; $i < $count; ++$i) {
            if ($i % 8 === 0) {
                $group = OverflowMenuGroup::new();
                $menu->addGroup($group);
            }

            $action = MenuActionItem::new('Action '.$i)->setCustomId('action-'.$i);
            $action->id = 100 + $i;
            $group->addComponent($action);
        }

        return $menu;
    }

    protected static function expectedMenu(): array
    {
        return [
            'type' => 25,
            'groups' => [
                ['components' => [
                    ['type' => 26, 'label' => 'View', 'style' => 1, 'id' => 2, 'custom_id' => 'view'],
                    ['type' => 26, 'label' => 'Archive', 'style' => 2, 'custom_id' => 'archive', 'description' => 'Hide this entry', 'disabled' => true, 'emoji' => ['name' => '⚠️']],
                ], 'label' => 'General'],
                ['components' => [
                    ['type' => 26, 'label' => 'Docs', 'style' => 3, 'url' => 'https://example.com/docs'],
                ]],
            ],
            'id' => 13,
        ];
    }

    protected static function encode(JsonSerializable $builder): array
    {
        return json_decode(json_encode($builder, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
