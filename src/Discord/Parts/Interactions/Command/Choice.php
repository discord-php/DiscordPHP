<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Command;

use Discord\Discord;
use Discord\Parts\Part;

use function Discord\poly_strlen;

/**
 * Choice represents a choice that can be given to a command.
 *
 * @see https://discord.com/developers/docs/interactions/application-commands#application-command-object-application-command-option-choice-structure
 *
 * @property string           $name  1-100 character choice name.
 * @property string|int|float $value Value of the choice, up to 100 characters if string.
 */
class Choice extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['name', 'value'];

    /**
     * Creates a new Choice builder.
     *
     * @param Discord          $discord
     * @param string           $name    name of the choice
     * @param string|int|float $value   value of the choice
     *
     * @return $this
     */
    public static function new(Discord $discord, string $name, $value): self
    {
        return new static($discord, ['name' => $name, 'value' => $value]);
    }

    /**
     * Sets the name of the choice.
     *
     * @param string $name name of the choice
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $namelen = poly_strlen($name);
        if ($namelen < 1) {
            throw new \LengthException('Choice name can not be empty.');
        } elseif ($namelen > 100) {
            throw new \LengthException('Choice name must be less than or equal to 100 characters.');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Sets the value of the choice.
     *
     * @param string|int|float $value value of the choice
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setValue($value): self
    {
        if (is_string($value) && poly_strlen($value) > 100) {
            throw new \LengthException('Choice value must be less than or equal to 100 characters.');
        }

        $this->value = $value;

        return $this;
    }
}
