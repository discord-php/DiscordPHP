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

namespace Discord\Builders\Components;

/**
 * A Checkbox Group is an interactive component for selecting one or many options via checkboxes. Checkbox Groups are available in modals and must be placed inside a Label.
 *
 * @link https://discord.com/developers/docs/components/reference#checkbox-group
 *
 * @since 10.46.0
 *
 * @property int       $type      21 for a radio group.
 * @property ?int|null $id        Optional identifier for component.
 * @property string    $custom_id Custom ID to send with interactive component.
 * @property ?bool     $required  Whether selecting an option is required or not.
 */
abstract class Group extends Interactive
{
    /**
     * Whether selecting an option is required or not.
     *
     * @var bool|null
     */
    protected $required;

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
