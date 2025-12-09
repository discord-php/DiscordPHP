<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders;

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Command\Option;
use Discord\Repository\Guild\GuildCommandRepository;
use Discord\Repository\Interaction\GlobalCommandRepository;
use JsonSerializable;

/**
 * Helper class used to build application commands.
 *
 * @since 7.0.0
 *
 * @author Mark `PeanutNL` Versluis
 */
class CommandBuilder extends Builder implements JsonSerializable
{
    use CommandAttributes;

    /**
     * Type of the command. The type defaults to 1.
     *
     * @var int
     */
    protected $type = Command::CHAT_INPUT;

    /**
     * Name of the command.
     *
     * @var string
     */
    protected string $name;

    /**
     * Description of the command. should be empty if the type is not CHAT_INPUT.
     *
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * Set of permissions represented as a bit set.
     *
     * @var string|null
     */
    protected ?string $default_member_permissions = null;

    /**
     * The default permission of the command. If true the command is enabled when the app is added to the guild. SOON DEPRECATED.
     *
     * @var bool|null
     */
    protected ?bool $default_permission = null;

    /**
     * Interaction context(s) where the command can be used, only for globally-scoped commands.
     *
     * @var int[]|null
     */
    protected $integration_types = null;

    /**
     * Interaction context(s) where the command can be used, only for globally-scoped commands.
     *
     * @var ExCollectionInterface<int>|int[]|null
     */
    protected $contexts = null;

    /**
     * The parameters for the command, max 25. Only for Slash command (CHAT_INPUT).
     *
     * @var ExCollectionInterface<Option>|Option[]|null
     */
    protected $options = null;

    /**
     * Creates a new command builder.
     *
     * @return static
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * Creates the command in the given repository.
     *
     * @param GlobalCommandRepository|GuildCommandRepository $repository
     *
     * @return Command
     *
     * @since 10.41.0
     */
    public function create(GlobalCommandRepository|GuildCommandRepository $repository): Command
    {
        return $repository->create($this->jsonSerialize());
    }

    /**
     * Returns all the options in the command.
     *
     * @return ExCollectionInterface<Option>|Option[]
     */
    public function getOptions()
    {
        return $this->options ?? Collection::for(Option::class, 'name');
    }

    /**
     * Returns an array with all the options.
     *
     * @throws \LengthException
     * @throws \DomainException
     *
     * @return array
     *
     * @deprecated 10.42.0 Use `jsonSerialize`
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $arrCommand = [
            'name' => $this->name,
            'description' => $this->description,
        ];

        $optionals = [
            'type',
            'name_localizations',
            'description_localizations',
            'default_member_permissions',
            'dm_permission',
            'default_permission',
            'guild_id',
            'nsfw',
            'integration_types',
            'contexts',
            'handler',
        ];

        foreach ($optionals as $optional) {
            if (property_exists($this, $optional) && $this->$optional !== null) {
                $arrCommand[$optional] = $this->$optional;
            }
        }

        $this->options ??= Collection::for(Option::class, 'name');

        /** @var Option $option */
        foreach ($this->options as $option) {
            $arrCommand['options'][] = $option->getRawAttributes();
        }

        return $arrCommand;
    }
}
