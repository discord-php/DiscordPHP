<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders;

use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Command\Option;
use JsonSerializable;

use function Discord\poly_strlen;

/**
 * Helper class used to build application commands.
 *
 * @author Mark `PeanutNL` Versluis
 */
class CommandBuilder implements JsonSerializable
{
    /**
     * Type of the command. The type defaults to 1.
     *
     * @var int
     */
    protected int $type = Command::CHAT_INPUT;

    /**
     * Name of the command.
     *
     * @var string
     */
    protected string $name;

    /**
     * Description of the command. should be emtpy if the type is not CHAT_INPUT.
     *
     * @var string
     */
    protected string $description = '';

    /**
     * array with options.
     *
     * @var Option[]
     */
    protected array $options = [];

    /**
     * The default permission of the command. If true the command is enabled when the app is added to the guild.
     *
     * @var bool
     */
    protected bool $default_permission = true;

    /**
     * Creates a new command builder.
     *
     * @return $this
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * Sets the type of the command.
     *
     * @param int $type Type of the command
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setType(int $type): self
    {
        if ($type < 1 || $type > 3) {
            throw new \InvalidArgumentException('Invalid type provided.');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Sets the name of the command.
     *
     * @param string $description Name of the command. Slash command names are lowercase.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        if (poly_strlen($name) > 100) {
            throw new \LengthException('Command name must be less than or equal to 32 characters.');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Sets the description of the command.
     *
     * @param string $description Description of the command
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setDescription(string $description): self
    {
        if ($this->type == Command::CHAT_INPUT && poly_strlen($description) > 100) {
            throw new \LengthException('Command description must be less than or equal to 100 characters.');
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Sets the default permission of the command.
     *
     * @param bool $permission Default permission of the command
     *
     * @return $this
     */
    public function setDefaultPermission(bool $permission): self
    {
        $this->default_permission = $permission;

        return $this;
    }

    /**
     * Adds an option to the command.
     *
     * @param Option $option The option
     *
     * @throws \OverflowException
     *
     * @return $this
     */
    public function addOption(Option $option): self
    {
        if (count($this->options) >= 25) {
            throw new \OverflowException('Command can only have a maximum of 25 options.');
        }

        $this->options[] = $option;

        return $this;
    }

    /**
     * Removes an option from the command.
     *
     * @param Option $option Option to remove.
     *
     * @return $this
     */
    public function removeOption(Option $option): self
    {
        if (($idx = array_search($option, $this->option)) !== null) {
            array_splice($this->options, $idx, 1);
        }

        return $this;
    }

    /**
     * Returns all the options in the command.
     *
     * @return Option[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Returns an array with all the options.
     *
     * @throws \LengthException
     * @throws \DomainException
     *
     * @return array
     */
    public function toArray(): array
    {
        if (poly_strlen($this->name) < 1) {
            throw new \LengthException('Command name must be greater than or equal to 1 character.');
        }

        $desclen = poly_strlen($this->description);
        if ($this->type == Command::CHAT_INPUT) {
            if ($desclen < 1) {
                throw new \LengthException('Description must be greater than or equal to 1 character.');
            }
        } elseif ($this->type == Command::USER || $this->type == Command::MESSAGE) {
            if ($desclen) {
                throw new \DomainException('Only a command with type CHAT_INPUT accepts a description.');
            }
        }

        $arrCommand = [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'options' => [],
            'default_permission' => $this->default_permission,
        ];

        foreach ($this->options as $option) {
            $arrCommand['options'][] = $option->getRawAttributes();
        }

        return $arrCommand;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
