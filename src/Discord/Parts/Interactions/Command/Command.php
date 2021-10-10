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

use Discord\Helpers\Collection;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

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
}
