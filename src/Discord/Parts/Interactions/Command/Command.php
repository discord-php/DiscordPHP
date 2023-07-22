<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Command;

use Discord\Helpers\Collection;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

/**
 * Represents a command registered on the Discord servers.
 *
 * @link https://discord.com/developers/docs/interactions/application-commands#application-command-object-application-command-structure
 *
 * @since 7.0.0
 *
 * @property      string      $id             The unique identifier of the command.
 * @property      string      $application_id The unique identifier of the parent Application that made the command, if made by one.
 * @property      string|null $guild_id       The unique identifier of the guild that the command belongs to. Null if global.
 * @property-read Guild|null  $guild          The guild that the command belongs to. Null if global.
 * @property      string      $version        Autoincrementing version identifier updated during substantial record changes.
 */
class Command extends Part
{
    use \Discord\Builders\CommandAttributes;

    /** Slash commands; a text-based command that shows up when a user types / */
    public const CHAT_INPUT = 1;

    /** A UI-based command that shows up when you right click or tap on a user */
    public const USER = 2;

    /** A UI-based command that shows up when you right click or tap on a message */
    public const MESSAGE = 3;

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'type',
        'application_id',
        'guild_id',
        'name',
        'name_localizations',
        'description',
        'description_localizations',
        'options',
        'default_member_permissions',
        'dm_permission',
        'default_permission',
        'nsfw',
        'version',
    ];

    /**
     * Gets the application id attribute.
     *
     * @return string Application ID of this Bot if not set.
     */
    protected function getApplicationIdAttribute(): string
    {
        if (! isset($this->attributes['application_id'])) {
            return $this->discord->application->id;
        }

        return $this->attributes['application_id'];
    }

    /**
     * Gets the options attribute.
     *
     * @return Collection|Options[]|null A collection of options.
     */
    protected function getOptionsAttribute(): ?Collection
    {
        if (! isset($this->attributes['options']) && (isset($this->type) && $this->type != self::CHAT_INPUT)) {
            return null;
        }

        $options = Collection::for(Option::class, null);

        foreach ($this->attributes['options'] ?? [] as $option) {
            $options->pushItem($this->createOf(Option::class, $option));
        }

        return $options;
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild attribute. `null` for global command.
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (! isset($this->attributes['guild_id'])) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/interactions/application-commands#create-global-application-command-json-params
     * @link https://discord.com/developers/docs/interactions/application-commands#create-guild-application-command-json-params
     */
    public function getCreatableAttributes(): array
    {
        $attr = [
            'name' => $this->name,
        ];

        $attr += $this->makeOptionalAttributes([
            'name_localizations' => $this->name_localizations,
            'description' => $this->description,
            'description_localizations' => $this->description_localizations,
            'options',
            'default_member_permissions' => $this->default_member_permissions,
            'default_permission' => $this->default_permission,
            'type' => $this->type,
            'nsfw' => $this->nsfw,

            'dm_permission' => $this->dm_permission,  // Guild command might omit this fillable
        ]);

        return $attr;
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/interactions/application-commands#edit-global-application-command-json-params
     */
    public function getUpdatableAttributes(): array
    {
        $attr = $this->makeOptionalAttributes([
            'name' => $this->name,
            'name_localizations' => $this->name_localizations,
            'description' => $this->description,
            'description_localizations' => $this->description_localizations,
            'options',
            'default_member_permissions' => $this->default_member_permissions,
            'default_permission' => $this->default_permission,
            'nsfw' => $this->nsfw,
        ]);

        if (! isset($this->guild_id)) {
            $attr += $this->makeOptionalAttributes([
                'dm_permission' => $this->dm_permission,
            ]);
        }

        return $attr;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_id' => $this->guild_id,
            'application_id' => $this->application_id,
            'command_id' => $this->id,
        ];
    }

    /**
     * Returns a formatted mention of the command.
     *
     * @return string A formatted mention of the command.
     */
    public function __toString(): string
    {
        return "</{$this->name}:{$this->id}>";
    }
}
