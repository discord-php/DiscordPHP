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
 *
 * @property int                      $type                      The type of the command, defaults 1 if not set.
 * @property string                   $name                      1-32 character name of the command.
 * @property string[]|null            $name_localizations        Localization dictionary for the name field. Values follow the same restrictions as name.
 * @property string                   $description               1-100 character description for CHAT_INPUT commands, empty string for USER and MESSAGE commands.
 * @property string[]|null            $description_localizations Localization dictionary for the description field. Values follow the same restrictions as description.
 * @property Collection|Option[]|null $options                   The parameters for the command, max 25. Only for Slash command (CHAT_INPUT).
 * @property bool                     $default_permission        Whether the command is enabled by default when the app is added to a guild.
 */
trait CommandAttributes {
    /**
     * Sets the type of the command.
     *
     * @param int $type Type of the command.
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
     * @param string $name Name of the command. Slash command names are lowercase.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $nameLen = poly_strlen($name);
        if ($nameLen < 1 || $nameLen > 100) {
            throw new \LengthException('Command name can be only 1 to 32 characters long.');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Sets the name of the command in another language.
     *
     * @param string      $locale Discord locale code.
     * @param string|null $name   Localized name of the command. Slash command names are lowercase.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setNameLocalization(string $locale, ?string $name): self
    {
        if (isset($name)) {
            $nameLen = poly_strlen($name);
            if ($nameLen < 1 || $nameLen > 100) {
                throw new \LengthException('Command name can be only 1 to 32 characters long.');
            }
        }

        $this->name_localizations[$locale] = $name;

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
        $descriptionLen = poly_strlen($description);
        if ($descriptionLen < 1 || $descriptionLen > 100) {
            throw new \LengthException('Command Description can be only 1 to 100 characters long.');
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
}
