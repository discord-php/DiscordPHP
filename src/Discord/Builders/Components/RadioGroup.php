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
 * A Radio Group is an interactive component for selecting exactly one option from a defined list. Radio Groups are available in modals and must be placed inside a Label.
 *
 * @link https://discord.com/developers/docs/components/reference#radio-group
 *
 * @since 10.46.0
 *
 * @property int                $type      21 for a radio group.
 * @property ?int|null          $id        Optional identifier for component.
 * @property string             $custom_id Developer-defined identifier for the input; 1-100 characters.
 * @property RadioGroupOption[] $options   List of options to render; min 2, max 10.
 * @property ?bool|null         $required  Whether a selection is required to submit the modal (defaults to `true`).
 */
class RadioGroup extends Group
{
    /**
     * @inheritDoc
     */
    public const USAGE = ['Modal'];

    /**
     * @inheritDoc
     */
    protected $type = ComponentObject::TYPE_RADIO_GROUP;

    /**
     * Creates a new radio group.
     *
     * @param string|null $custom_id custom ID of the radio group. If not given, a UUID will be used.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(?string $custom_id = null)
    {
        $this->setCustomId($custom_id ?? self::generateUuid());
    }
    
    /**
     * Creates a new radio group component.
     *
     * @param string|null $custom_id ID for the radio group.
     *
     * @return self
     */
    public static function new(?string $custom_id = null): self
    {
        return new self($custom_id);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
            'custom_id' => $this->custom_id,
            'options' => $this->options,
        ];

        if (count($this->options) < 2 || count($this->options) > 10) {
            throw new \DomainException('RadioGroup must have between 2 and 10 options.');
        }

        if (isset($this->required)) {
            $content['required'] = $this->required;
        }

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        return $content;
    }
}
