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

use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

/**
 * Represents a command registered on the Discord servers.
 *
 * @property string         $id                         The unique identifier of the command.
 * @property string         $type                       The type of the command.
 * @property string         $application_id             Application that made the command, if made by one.
 * @property Guild|null     $guild                      The guild that the command belongs to. Null if global.
 * @property string|null    $guild_id                   The unique identifier of the guild that the command belongs to. Null if global.
 * @property string         $name                       The name of the command.
 * @property string         $description                1-100 character description.
 * @property Option[]       $options                    The parameters for the command, max 25. Only for Slash command (CHAT_INPUT).
 * @property boolean        $default_permission         Whether the command is enabled by default when the app is added to a guild.
 */
class Command extends Part
{
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
    ];

    /**
     * @inheritdoc
     */
    protected function afterConstruct(): void
    {
        // @todo registerCommand
        if (! $this->offsetExists('application_id')) {
            if (isset($this->discord->application->id)) {
                $this->offsetSet('application_id', $this->discord->application->id);
            } else if ($guild = $this->getGuildAttribute()) {
                $this->offsetSet('application_id', $guild->application_id);
            }
        }
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild The guild attribute.
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
