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

/**
 * Helper class used to build application commands.
 *
 * @author Mark `PeanutNL` Versluis
 */
class CommandBuilder implements JsonSerializable
{
    use CommandAttributes;

    /**
     * Type of the command. The type defaults to 1.
     *
     * @var int
     */
    protected $type = Command::CHAT_INPUT;

    /**
     * The default permission of the command. If true the command is enabled when the app is added to the guild.
     *
     * @var bool
     */
    protected $default_permission = true;

    /**
     * Creates a new command builder.
     *
     * @return self
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * Returns all the options in the command.
     *
     * @return Option[]|null
     */
    public function getOptions(): ?array
    {
        return $this->options ?? null;
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
        $arrCommand = [
            'name' => $this->name,
            'description' => $this->description,
        ];

        $optionals = [
            'type',
            'name_localizations',
            'description_localizations',
            'default_member_permissions',
            'default_permission',
        ];

        foreach ($optionals as $optional) {
            if (property_exists($this, $optional)) {
                $arrCommand[$optional] = $this->$optional;
            }
        }

        foreach ($this->options ?? [] as $option) {
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
