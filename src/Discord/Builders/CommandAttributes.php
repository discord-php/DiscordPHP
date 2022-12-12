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
 * Application Command attributes.
 *
 * @see \Discord\Builders\CommandBuilder
 * @see \Discord\Parts\Interactions\Command\Command
 *
 * @since 7.1.0
 *
 * @property int                      $type                       The type of the command, defaults 1 if not set.
 * @property string                   $name                       1-32 character name of the command.
 * @property ?string[]|null           $name_localizations         Localization dictionary for the name field. Values follow the same restrictions as name.
 * @property string                   $description                1-100 character description for CHAT_INPUT commands, empty string for USER and MESSAGE commands.
 * @property ?string[]|null           $description_localizations  Localization dictionary for the description field. Values follow the same restrictions as description.
 * @property Collection|Option[]|null $options                    The parameters for the command, max 25. Only for Slash command (CHAT_INPUT).
 * @property ?string                  $default_member_permissions Set of permissions represented as a bit set.
 * @property bool|null                $dm_permission              Indicates whether the command is available in DMs with the app, only for globally-scoped commands. By default, commands are visible.
 * @property ?bool                    $default_permission         Whether the command is enabled by default when the app is added to a guild. SOON DEPRECATED.
 * @property bool|null                $nsfw                       Indicates whether the command is age-restricted, defaults to `false`.
 */
trait CommandAttributes
{
    /**
     * Sets the type of the command.
     *
     * @param int $type Type of the command.
     *
     * @throws \InvalidArgumentException `$type` is not 1-3.
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
     * @throws \LengthException `$name` is not 1-32 characters long.
     * @throws \DomainException `$name` contains invalid characters.
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $nameLen = poly_strlen($name);
        if ($nameLen < 1) {
            throw new \LengthException('Command name can not be empty.');
        } elseif ($nameLen > 32) {
            throw new \LengthException('Command name can be only up to 32 characters long.');
        }

        if (isset($this->type) && $this->type == Command::CHAT_INPUT && preg_match('/^[-_\p{L}\p{N}\p{Devanagari}\p{Thai}]{1,32}$/u', $name) === 0) {
            throw new \DomainException('Slash command name contains invalid characters.');
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
     * @throws \LengthException `$name` is not 1-32 characters long.
     * @throws \DomainException `$name` contains invalid characters.
     *
     * @return $this
     */
    public function setNameLocalization(string $locale, ?string $name): self
    {
        if (isset($name)) {
            $nameLen = poly_strlen($name);
            if ($nameLen < 1) {
                throw new \LengthException('Command name can not be empty.');
            } elseif ($nameLen > 32) {
                throw new \LengthException('Command name can be only up to 32 characters long.');
            }

            if (isset($this->type) && $this->type == Command::CHAT_INPUT && preg_match('/^[-_\p{L}\p{N}\p{Devanagari}\p{Thai}]{1,32}$/u', $name) === 0) {
                throw new \DomainException('Slash command localized name contains invalid characters.');
            }
        }

        $this->name_localizations ??= [];

        $this->name_localizations[$locale] = $name;

        return $this;
    }

    /**
     * Sets the description of the command.
     *
     * @param string $description Description of the command
     *
     * @throws \LengthException `$description` is not 1-100 characters long.
     *
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $descriptionLen = poly_strlen($description);
        if ($descriptionLen < 1) {
            throw new \LengthException('Command description can not be empty.');
        } elseif ($descriptionLen > 100) {
            throw new \LengthException('Command description can be only up to 100 characters long.');
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
     * @throws \LengthException `$description` is not 1-100 characters long.
     *
     * @return $this
     */
    public function setDescriptionLocalization(string $locale, ?string $description): self
    {
        if (isset($description, $this->type) && $this->type == Command::CHAT_INPUT && poly_strlen($description) > 100) {
            throw new \LengthException('Command description must be less than or equal to 100 characters.');
        }

        $this->description_localizations ??= [];

        $this->description_localizations[$locale] = $description;

        return $this;
    }

    /**
     * Sets the default permission of the command.
     *
     * @deprecated 7.1.0 See `CommandAttributes::setDefaultMemberPermissions()`.
     *
     * @param ?bool $permission Default permission of the command
     *
     * @return $this
     */
    public function setDefaultPermission(?bool $permission): self
    {
        $this->default_permission = $permission;

        return $this;
    }

    /**
     * Sets the default member permissions of the command.
     *
     * @param string|int $permissions Default member permission bits of the command.
     *
     * @return $this
     */
    public function setDefaultMemberPermissions($permissions): self
    {
        $this->default_member_permissions = (string) $permissions;

        return $this;
    }

    /**
     * Sets the DM permission of the command.
     *
     * @param bool $permission DM permission of the command.
     *
     * @return $this
     */
    public function setDmPermission(bool $permission): self
    {
        $this->dm_permission = $permission;

        return $this;
    }

    /**
     * Sets the age restriction of the command.
     *
     * @param bool $restricted Age restriction of the command.
     *
     * @return $this
     */
    public function setNsfw(bool $restricted): self
    {
        $this->nsfw = $restricted;

        return $this;
    }

    /**
     * Adds an option to the command.
     *
     * @param Option $option The option.
     *
     * @throws \DomainException   Command type is not CHAT_INPUT (1).
     * @throws \OverflowException Command exceeds maximum 25 options.
     *
     * @return $this
     */
    public function addOption(Option $option): self
    {
        if (isset($this->type) && $this->type != Command::CHAT_INPUT) {
            throw new \DomainException('Only CHAT_INPUT Command type can have option.');
        }

        if (isset($this->options) && count($this->options) >= 25) {
            throw new \OverflowException('Command can only have a maximum of 25 options.');
        }

        $this->options ??= [];

        $this->options[] = $option;

        return $this;
    }

    /**
     * Removes an option from the command.
     *
     * @param Option $option Option to remove.
     *
     * @throws \DomainException Command type is not CHAT_INPUT (1).
     *
     * @return $this
     */
    public function removeOption(Option $option): self
    {
        if (isset($this->type) && $this->type != Command::CHAT_INPUT) {
            throw new \DomainException('Only CHAT_INPUT Command type can have option.');
        }

        if (isset($this->options) && ($idx = array_search($option, $this->options)) !== false) {
            array_splice($this->options, $idx, 1);
        }

        return $this;
    }

    /**
     * Clear all options from the command.
     *
     * @return $this
     */
    public function clearOptions(): self
    {
        $this->options = [];

        return $this;
    }
}
