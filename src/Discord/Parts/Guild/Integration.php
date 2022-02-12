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
use Discord\Parts\OAuth\Application;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * An Integration is a guild integrations for Twitch, Youtube, Bot and Apps.
 *
 * @see https://discord.com/developers/docs/resources/guild#integration-object
 *
 * @property string           $id                  Integration id.
 * @property string           $name                Integration name.
 * @property string           $type                Integration type (twitch, youtube, or discord).
 * @property bool             $enabled             Is this integration enabled?
 * @property bool|null        $syncing             Is this integration syncing?
 * @property string|null      $role_id             Id that this integration uses for "subscribers".
 * @property Role|null        $role                Role that this integration uses for "subscribers".
 * @property bool|null        $enable_emoticons    Whether emoticons should be synced for this integration (twitch only currently).
 * @property int|null         $expire_behavior     The behavior of expiring subscribers.
 * @property int|null         $expire_grace_period The grace period (in days) before expiring subscribers.
 * @property User|null        $user                User for this integration.
 * @property object           $account             Integration account information.
 * @property Carbon|null      $synced_at           When this integration was last synced.
 * @property int|null         $subscriber_count    How many subscribers this integration has.
 * @property bool|null        $revoked             Has this integration been revoked.
 * @property Application|null $application         The bot/OAuth2 application for discord integrations.
 * @property Guild|null       $guild
 */
class Integration extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
        'name',
        'type',
        'enabled',
        'syncing',
        'role_id',
        'enable_emoticons',
        'expire_behavior',
        'expire_grace_period',
        'user',
        'account',
        'synced_at',
        'subscriber_count',
        'revoked',
        'application',
        'guild_id',
    ];

    /**
     * Gets the user that created the integration.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        if (! isset($this->attributes['user'])) {
            return null;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->factory->part(User::class, (array) $this->attributes['user'], true);
    }

    /**
     * Returns the synced_at attribute.
     *
     * @throws \Exception
     *
     * @return Carbon|null The synced_at attribute.
     */
    protected function getSyncedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['synced_at'])) {
            return null;
        }

        return new Carbon($this->attributes['synced_at']);
    }

    /**
     * Returns the application attribute.
     *
     * @todo return correct Application structure https://discord.com/developers/docs/resources/guild#integration-application-object
     *
     * @return Application|null
     */
    protected function getApplicationAttribute(): ?Application
    {
        if (! isset($this->attributes['application'])) {
            return null;
        }

        if ($this->attributes['application']->id == $this->discord->application->id) {
            return $this->discord->application;
        }

        $application = $this->factory->part(Application::class, (array) $this->attributes['application'], true);

        return $application;
    }

    /**
     * Returns the guild attribute of the integration.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the "subscribers" role that this integration used only if guild is cached.
     *
     * @return Role|null
     */
    protected function getRoleAttribute(): ?Role
    {
        if ($this->guild) {
            return $this->guild->roles->get('id', $this->attributes['role_id']);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_id' => $this->guild_id,
            'integration_id' => $this->id,
        ];
    }
}
