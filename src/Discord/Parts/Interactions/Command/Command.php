<?php

/*
 * This file was a part of the DiscordPHP-Slash project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license which is
 * bundled with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Command;

use Discord\Exceptions\InvalidOverwriteException;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\Guild\OverwriteRepository;
use React\Promise\ExtendedPromiseInterface;

/**
 * Represents a command registered on the Discord servers.
 *
 * @property string              $id                 The unique identifier of the command.
 * @property string              $type               The type of the command, defaults 1 if not set
 * @property string              $application_id     The unique identifier of the parent Application that made the command, if made by one.
 * @property Guild|null          $guild              The guild that the command belongs to. Null if global.
 * @property string|null         $guild_id           The unique identifier of the guild that the command belongs to. Null if global.
 * @property string              $name               1-32 character name of the command.
 * @property string              $description        1-100 character description for CHAT_INPUT commands, empty string for USER and MESSAGE commands
 * @property Collection|Option[] $options            The parameters for the command, max 25. Only for Slash command (CHAT_INPUT).
 * @property boolean             $default_permission Whether the command is enabled by default when the app is added to a guild.
 * @property string              $version            Autoincrementing version identifier updated during substantial record changes
 * @property OverwriteRepository $overwrites         permission overwrites
 */
class Command extends Part
{
    /** Previously known as Slash Command */
    public const CHAT_INPUT = 1;
    public const USER = 2;
    public const MESSAGE = 3;

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
        'type',
        'application_id',
        'guild_id',
        'name',
        'description',
        'options',
        'default_permission',
        'version'
    ];

    /**
     * @inheritdoc
     */
    protected $visible = ['options'];

    /**
     * @inheritdoc
     */
    protected $repositories = [
        'overwrites' => OverwriteRepository::class
    ];

    /**
     * @inheritdoc
     */
    protected function afterConstruct(): void
    {
        if (! isset($this->attributes['application_id'])) {
            $this->offsetSet('application_id', $this->discord->application->id);
        }
    }

    /**
     * Gets the options attribute.
     *
     * @return Collection|Options[] A collection of options.
     */
    protected function getOptionsAttribute(): Collection
    {
        $options = new Collection([], null);

        foreach ($this->attributes['options'] ?? [] as $option) {
            $options->push($this->factory->create(Option::class, $option, true));
        }

        return $options;
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild attribute. Null for global command.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        return [
            'type' => $this->type,
            'guild_id' => $this->guild_id,
            'name' => $this->name,
            'description' => $this->description,
            'options' => $this->options,
            'default_permission' => $this->default_permission,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'type' => $this->type,
            'guild_id' => $this->guild_id,
            'name' => $this->name,
            'description' => $this->description,
            'options' => $this->options,
            'default_permission' => $this->default_permission,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'command_id' => $this->id,
            'guild_id' => $this->guild_id,
            'application_id' => $this->application_id,
        ];
    }

    /**
     * Sets an overwrite to the application command.
     *
     * @param Part      $part      A role or user.
     * @param Overwrite $overwrite An overwrite object.
     *
     * @return ExtendedPromiseInterface
     */
    public function setOverwrite(Part $part, Overwrite $overwrite): ExtendedPromiseInterface
    {
        if (!($part instanceof Role || $part instanceof User || $part instanceof Member)) {
            return \React\Promise\reject(new InvalidOverwriteException('Given part was not one of role or user.'));
        }

        return $this->http->put(Endpoint::bind(Endpoint::GUILD_APPLICATION_COMMAND_PERMISSIONS, $this->application_id, $this->guild_id, $this->id), $overwrite->getRawAttributes());
    }
}
