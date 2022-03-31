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

use function Discord\poly_strlen;

/**
 * Application Command attributes
 *
 * @see Discord\Builders\CommandBuilder
 * @see Discord\Parts\Interactions\Command\Command
 */
trait CommandAttributes {
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
     * Sets the description of the command in another language.
     *
     * @param string      $locale      Discord locale code.
     * @param string|null $description Localized description of the command.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setDescriptionLocalization(string $locale, ?string $description): self
    {
        if (isset($description) && $this->type == Command::CHAT_INPUT && poly_strlen($description) > 100) {
            throw new \LengthException('Command description must be less than or equal to 100 characters.');
        }

        $this->description_localizations[$locale] = $description;

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
}
