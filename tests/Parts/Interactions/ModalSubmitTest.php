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

use Discord\Builders\Components\Label;
use Discord\Builders\Components\TextInput;
use Discord\Builders\ModalBuilder;
use Discord\Discord;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Message\Component;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\MessageComponent;
use Discord\Parts\Interactions\ModalSubmit;
use Discord\WebSockets\Event;

/**
 * The callback given to respondWithModal() receives the submitted fields, whichever layout
 * holds them: a Label (one field each) or an action row (the older layout).
 */
final class ModalSubmitTest extends DiscordTestCase
{
    public function testFieldsInsideLabelsReachTheSubmitCallback()
    {
        return $this->submit([
            ['type' => 18, 'id' => 1, 'component' => ['type' => 4, 'id' => 2, 'custom_id' => 'instructions', 'value' => 'Drop the Steam part']],
            ['type' => 18, 'id' => 3, 'component' => ['type' => 4, 'id' => 4, 'custom_id' => 'tone', 'value' => 'Upbeat']],
        ], ['instructions' => 'Drop the Steam part', 'tone' => 'Upbeat']);
    }

    public function testFieldsInsideActionRowsStillReachTheSubmitCallback()
    {
        return $this->submit([
            ['type' => 1, 'id' => 1, 'components' => [['type' => 4, 'id' => 2, 'custom_id' => 'instructions', 'value' => 'Shorter']]],
        ], ['instructions' => 'Shorter']);
    }

    /**
     * Opens a modal from a button click, submits `$fields` and checks the callback saw `$expected`.
     *
     * @param array<int, array<string, mixed>> $fields   The submitted `data.components`.
     * @param array<string, string>            $expected custom_id => value.
     */
    private function submit(array $fields, array $expected)
    {
        return wait(function (Discord $discord, $resolve) use ($fields, $expected) {
            $mock = getMockDiscord();
            $mock->getHttpClient()->setDriver(getMockHttpDriver(fn () => null));
            $factory = $mock->getFactory();
            // Gateway payloads arrive as decoded JSON objects.
            $payload = fn (array $data) => (array) json_decode(json_encode($data));

            $click = $factory->part(MessageComponent::class, $payload([
                'id' => '10', 'application_id' => '20', 'type' => Interaction::TYPE_MESSAGE_COMPONENT, 'token' => 'click', 'version' => 1,
                'user' => ['id' => '30', 'username' => 'owner', 'discriminator' => '0'],
                'data' => ['custom_id' => 'open', 'component_type' => 2],
            ]), true);
            $submit = $factory->part(ModalSubmit::class, $payload([
                'id' => '11', 'application_id' => '20', 'type' => Interaction::TYPE_MODAL_SUBMIT, 'token' => 'submit', 'version' => 1,
                'user' => ['id' => '30', 'username' => 'owner', 'discriminator' => '0'],
                'data' => ['custom_id' => 'edit', 'components' => $fields],
            ]), true);

            $modal = ModalBuilder::new('Edit', 'edit', [Label::new('What should change?', TextInput::new(null, TextInput::STYLE_PARAGRAPH, 'instructions'))]);
            $received = null;

            $click->respondWithModal($modal, function (Interaction $interaction, ExCollectionInterface $components) use (&$received) {
                $received = [];
                foreach ($components as $component) {
                    /** @var Component $component */
                    $received[$component->custom_id] = $component->value;
                }
            })
                ->then(function () use ($mock, $submit, &$received, $expected) {
                    $mock->emit(Event::INTERACTION_CREATE, [$submit, $mock]);

                    $this->assertSame($expected, $received);
                })
                ->then($resolve, $resolve);
        });
    }
}
