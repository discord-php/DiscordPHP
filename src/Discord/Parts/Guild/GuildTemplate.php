<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Carbon\Carbon;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * A Guild Template is a code that when used, creates a guild based on a snapshot of an existing guild.
 *
 * @link https://discord.com/developers/docs/resources/guild-template
 *
 * @property string      $code                    The template code (unique ID).
 * @property string      $name                    Template name.
 * @property string|null $description             The description for the template. Up to 120 characters.
 * @property int         $usage_count             Number of times this template has been used.
 * @property string      $creator_id              The ID of the user who created the template.
 * @property User        $creator                 The user who created the template.
 * @property Carbon      $created_at              A timestamp of when the template was created.
 * @property Carbon      $updated_at              When this template was last synced to the source guild.
 * @property string      $source_guild_id         The ID of the guild this template is based on.
 * @property Guild       $source_guild            The guild this template is based on.
 * @property object[]    $serialized_source_guild The guild snapshot this template contains.
 * @property bool        $is_dirty                Whether the template has unsynced changes.
 */
class GuildTemplate extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'usage_count',
        'creator_id',
        'creator',
        'created_at',
        'updated_at',
        'source_guild_id',
        'serialized_source_guild',
        'is_dirty',
    ];

    /**
     * Returns the id attribute.
     *
     * @return string The id attribute.
     */
    protected function getIdAttribute(): string
    {
        return $this->code;
    }

    /**
     * Returns the source guild attribute.
     *
     * @return Guild      The guild snapshot this template contains.
     * @throws \Exception
     */
    protected function getSourceGuildAttribute(): ?Guild
    {
        if (isset($this->attributes['serialized_source_guild']) && $guild = $this->discord->guilds->get('id', $this->attributes['source_guild_id'])) {
            return $guild;
        }

        return $this->factory->create(Guild::class, $this->attributes['serialized_source_guild'] ?? [], true);
    }

    /**
     * Gets the user that created the template.
     *
     * @return User
     */
    protected function getCreatorAttribute(): Part
    {
        if ($creator = $this->discord->users->get('id', $this->attributes['creator_id'])) {
            return $creator;
        }

        return $this->factory->part(User::class, (array) $this->attributes['creator'], true);
    }

    /**
     * Returns the created at attribute.
     *
     * @return Carbon     The time that the guild template was created.
     * @throws \Exception
     */
    protected function getCreatedAtAttribute(): Carbon
    {
        return new Carbon($this->attributes['created_at']);
    }

    /**
     * Returns the updated at attribute.
     *
     * @return Carbon     The time that the guild template was updated.
     * @throws \Exception
     */
    protected function getUpdatedAtAttribute(): Carbon
    {
        return new Carbon($this->attributes['updated_at']);
    }

    /**
     * Returns the template URL.
     *
     * @return string The URL to the guild template.
     */
    public function __toString(): string
    {
        return "https://discord.new/{$this->code}";
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'template_code' => $this->code,
        ];
    }
}
