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

namespace Discord\Builders\Components;

/**
 * Select menu for channels.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#select-menus
 *
 * @since 10.0.0
 *
 * @property int          $type           8 for a channel select.
 * @property ?int|null    $id             Optional identifier for component.
 * @property string       $custom_id      ID for the select menu; max 100 characters.
 * @property ?int[]|null  $channel_types  List of channel types to include in the channel select component.
 * @property ?string|null $placeholder    Placeholder text if nothing is selected; max 150 characters.
 * @property ?array|null  $default_values List of default values for auto-populated select menu components.
 * @property ?int|null    $min_values     Minimum number of items that must be chosen (defaults to 1); min 0, max 25.
 * @property ?int|null    $max_values     Maximum number of items that can be chosen (defaults to 1); max 25.
 * @property ?bool|null   $disabled       Whether select menu is disabled (defaults to false).
 */
class ChannelSelect extends SelectMenu
{
    /**
     * Component type.
     *
     * @var int
     */
    protected $type = ComponentObject::TYPE_CHANNEL_SELECT;

    /**
     * List of channel types to include.
     *
     * @var int[]
     */
    protected $channel_types = [];

    /**
     * Set the channel types of the select menu.
     *
     * @link https://discord.com/developers/docs/resources/channel#channel-object-channel-types
     *
     * @param ?int[] $channel_types Array of channel types.
     *
     * @return $this
     */
    public function setChannelTypes(?array $channel_types = null): self
    {
        $this->channel_types = $channel_types;

        return $this;
    }

    /**
     * Returns the array of channel types that the select menu has.
     *
     * @return array
     */
    public function getChannelTypes(): array
    {
        return $this->channel_types;
    }

    /**
     * Set if this component is required to be filled, default false. (Modal only).
     *
     * @param ?bool $required
     *
     * @return $this
     */
    public function setRequired(?bool $required = null): self
    {
        $this->required = $required;

        return $this;
    }
}
