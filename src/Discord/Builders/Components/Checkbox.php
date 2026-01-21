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
 * A Checkbox is a single interactive component for simple yes/no style questions. Checkboxes are available in modals and must be placed inside a Labl.
 *
 * @link https://discord.com/developers/docs/components/reference#checkbox
 *
 * @since 10.46.0
 *
 * @property int        $type      23 for a checkbox.
 * @property ?int|null  $id        Optional identifier for component.
 * @property string     $custom_id Developer-defined identifier for the input; 1-100 characters.
 * @property ?bool|null $value     Whether the checkbox is checked or not.
 */
class Checkbox extends Interactive
{
    /**
     * Component type.
     *
     * @var int
     */
    protected $type = ComponentObject::TYPE_CHECKBOX;

    /**
     * Whether the checkbox is checked or not.
     *
     * @var bool|null
     */
    protected $value;

    /**
     * Sets whether the checkbox is checked or not.
     *
     * @param bool|null $value Whether the checkbox is checked or not.
     *
     * @return $this
     */
    public function setValue(?bool $value = null): self
    {
        $this->value = $value;

        return $this;
    }

    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
            'custom_id' => $this->custom_id,
        ];

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        if (isset($this->value)) {
            $content['value'] = $this->value;
        }

        return $content;
    }
}
