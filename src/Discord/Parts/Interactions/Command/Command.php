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
use Discord\Parts\Guild\CommandPermissions;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\reject;

/**
 * Represents a command registered on the Discord servers.
 *
 * @see https://discord.com/developers/docs/interactions/application-commands#application-command-object-application-command-structure
 *
 * @property string      $id             The unique identifier of the command.
 * @property string      $application_id The unique identifier of the parent Application that made the command, if made by one.
 * @property string|null $guild_id       The unique identifier of the guild that the command belongs to. Null if global.
 * @property Guild|null  $guild          The guild that the command belongs to. Null if global.
 * @property string      $version        Autoincrementing version identifier updated during substantial record changes.
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
     * @inheritdoc
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
        if (! isset($this->attributes['options'])) {
            return null;
        }

        $options = Collection::for(Option::class, null);

        foreach ($this->attributes['options'] as $option) {
            $options->pushItem($this->factory->part(Option::class, (array) $option, true));
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
        if (! isset($this->attributes['guild_id'])) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Sets a guild application command permission overwrite.
     *
     * @see https://discord.com/developers/docs/interactions/application-commands#edit-application-command-permissions
     *
     * @param CommandPermissions $overwrite A command permission object.
     *
     * @deprecated 7.1.0 Requires Bearer token on Permissions v2.
     *
     * @return ExtendedPromiseInterface
     */
    public function setOverwrite(CommandPermissions $overwrite): ExtendedPromiseInterface
    {
        return reject(new \RuntimeException('Bots can no longer set guild command permissions'));
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        $attr = [
            'guild_id' => $this->guild_id ?? null,
            'name' => $this->name,
            'name_localizations' => $this->name_localizations,
            'description' => $this->description,
            'description_localizations' => $this->description_localizations,
            'options' => $this->attributes['options'] ?? null,
            'default_member_permissions' => $this->default_member_permissions,
            'default_permission' => $this->default_permission,
            'type' => $this->type,
        ];

        // Guild command might omit this fillable
        if (array_key_exists('dm_permission', $this->attributes)) {
            $attr['dm_permission'] = $this->dm_permission;
        }

        return $attr;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'guild_id' => $this->guild_id ?? null,
            'name' => $this->name,
            'name_localizations' => $this->name_localizations,
            'description' => $this->description,
            'description_localizations' => $this->description_localizations,
            'options' => $this->attributes['options'] ?? null,
            'default_member_permissions' => $this->default_member_permissions,
            'dm_permission' => $this->dm_permission,
            'default_permission' => $this->default_permission,
            'type' => $this->type,
        ];
    }

    /**
     * @inheritdoc
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
