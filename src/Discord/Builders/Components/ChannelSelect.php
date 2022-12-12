<?php

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
 */
class ChannelSelect extends SelectMenu
{
    /**
     * Component type.
     *
     * @var int
     */
    protected $type = Component::TYPE_CHANNEL_SELECT;

    /**
     * List of channel types to include.
     *
     * @var int[]
     */
    protected $channel_types = [];

    /**
     * Set the channel types of the select menu.
     *
     * @param int[] $channel_types Array of channel types.
     *
     * @return $this
     */
    public function setChannelTypes(array $channel_types): self
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
}
