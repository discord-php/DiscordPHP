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
    use CommandAttributes;

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
     * Localization dictionary for the name field. Values follow the same restrictions as name.
     *
     * @var string[]
     */
    protected array $name_localizations;

    /**
     * Description of the command. should be emtpy if the type is not CHAT_INPUT.
     *
     * @var string
     */
    protected string $description = '';

    /**
     * Localization dictionary for the description field. Values follow the same restrictions as description.
     *
     * @var string[]|null
     */
    protected array $description_localizations;

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
            'name_localizations' => $this->name_localizations,
            'description' => $this->description,
            'description_localizations' => $this->name_localizations,
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
