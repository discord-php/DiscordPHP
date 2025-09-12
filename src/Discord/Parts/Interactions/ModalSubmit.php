<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions;

use Discord\Helpers\Collection;
use Discord\Parts\Channel\Message\ActionRow;
use Discord\Parts\Channel\Message\Component;
use Discord\Parts\Channel\Message\Label;
use Discord\Parts\Interactions\Request\ModalSubmitData;
use Discord\WebSockets\Event;
use React\EventLoop\TimerInterface;

/**
 * @since 10.19.0
 *
 * @property ModalSubmitData $data Data associated with the interaction.
 */
class ModalSubmit extends Interaction
{
    /**
     * Type of the interaction.
     *
     * @var int
     */
    protected $type = Interaction::TYPE_MODAL_SUBMIT;

    /**
     * Returns the data associated with the interaction.
     *
     * @return ModalSubmitData
     */
    protected function getDataAttribute(): ModalSubmitData
    {
        $adata = $this->attributes['data'];
        if (! isset($adata->guild_id) && isset($this->attributes['guild_id'])) {
            $adata->guild_id = $this->guild_id;
        }

        return $this->createOf(ModalSubmitData::class, $adata);
    }

    /**
     * Creates a listener callback for handling modal submit interactions with a specific custom ID.
     *
     * @param string         $custom_id The custom ID to match against the interaction's custom_id.
     * @param callable       $submit    The callback to execute when the interaction matches. Receives the interaction and a collection of components.
     * @param int|float|null $timeout   Optional timeout in seconds after which the listener will be removed. (Mandatory for modal submit interactions)
     *
     * @return callable The listener callback to be registered for interaction events.
     */
    protected function createListener(string $custom_id, callable $submit, int|float|null $timeout = null): callable
    {
        $timer = null;

        $listener = function (ModalSubmit $interaction) use ($custom_id, $submit, &$listener, &$timer) {
            if ($interaction->data->custom_id != $custom_id) {
                return;
            }

            $components = Collection::for(Component::class, 'custom_id');
            foreach ($interaction->data->components as $container) {
                $container = $this->createOf(Component::TYPES[$container->type ?? 0], $container);
                if (property_exists($container, 'components')) { // e.g. ActionRow
                    foreach ($container->components as $component) {
                        /** @var Component $component */
                        $component = $this->createOf(Component::TYPES[$container->type ?? 0], $component);
                        $components->pushItem($component);
                    }
                } elseif (property_exists($container, 'component')) { // e.g. Label
                    /** @var Component $component */
                    $component = $this->createOf(Component::TYPES[$container->type ?? 0], $container->component);
                    $components->pushItem($component);
                }
            }

            $submit($interaction, $components);
            $this->discord->removeListener(Event::INTERACTION_CREATE, $listener);

            /** @var ?TimerInterface $timer */
            if ($timer !== null) {
                $this->discord->getLoop()->cancelTimer($timer);
            }
        };

        if ($timeout) {
            $timer = $this->discord->getLoop()->addTimer($timeout, fn () => $this->discord->removeListener(Event::INTERACTION_CREATE, $listener));
        }

        return $listener;
    }
}
