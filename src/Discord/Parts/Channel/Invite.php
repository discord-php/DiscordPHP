<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Carbon\Carbon;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Parts\OAuth\Application;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * An invite to a Channel and Guild.
 *
 * @link https://discord.com/developers/docs/resources/invite
 *
 * @since 7.0.0 Namespace moved from Guild to Channel
 * @since 2.0.0
 *
 * @property string              $code                       The invite code.
 * @property Guild|null          $guild                      The partial guild that the invite is for.
 * @property string|null         $guild_id
 * @property Channel             $channel                    The partial channel that the invite is for.
 * @property string|null         $channel_id
 * @property User|null           $inviter                    The user that created the invite.
 * @property int|null            $target_type                The type of target for this voice channel invite.
 * @property User|null           $target_user                The user whose stream to display for this voice channel stream invite.
 * @property Application|null    $target_application         The partial embedded application to open for this voice channel embedded application invite.
 * @property int|null            $approximate_presence_count Approximate count of online members, returned from the GET /invites/<code> endpoint when with_counts is true.
 * @property int|null            $approximate_member_count   Approximate count of total members, returned from the GET /invites/<code> endpoint when with_counts is true.
 * @property Carbon|null         $expires_at                 The expiration date of this invite, returned from the GET /invites/<code> endpoint when with_expiration is true.
 * @property ScheduledEvent|null $guild_scheduled_event      Guild scheduled event data, only included if guild_scheduled_event_id contains a valid guild scheduled event id.
 *
 * @property int|null    $uses       How many times the invite has been used.
 * @property int|null    $max_uses   How many times the invite can be used.
 * @property int|null    $max_age    How many seconds the invite will be alive.
 * @property bool|null   $temporary  Whether the invite is for temporary membership.
 * @property Carbon|null $created_at A timestamp of when the invite was created.
 *
 * @property-read string $invite_url Returns the invite URL.
 */
class Invite extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'code',
        'guild',
        'channel',
        'inviter',
        'target_type',
        'target_user',
        'target_application',
        'approximate_presence_count',
        'approximate_member_count',
        'expires_at',
        'guild_scheduled_event',

        // Extra metadata
        'uses',
        'max_uses',
        'max_age',
        'temporary',
        'created_at',

        // @internal
        'guild_id',
        'channel_id',
    ];

    public const TARGET_TYPE_STREAM = 1;
    public const TARGET_TYPE_EMBEDDED_APPLICATION = 2;

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
     * Returns the guild attribute.
     *
     * @return Guild|null The Guild that you have been invited to.
     */
    protected function getGuildAttribute(): ?Guild
    {
        $guildId = $this->guild_id;

        if ($guildId && $guild = $this->discord->guilds->get('id', $guildId)) {
            return $guild;
        }

        if (! isset($this->attributes['guild'])) {
            return null;
        }

        return $this->factory->part(Guild::class, (array) $this->attributes['guild'], true);
    }

    /**
     * Returns the guild id attribute.
     *
     * @return string|null
     */
    protected function getGuildIdAttribute(): ?string
    {
        if (isset($this->attributes['guild_id'])) {
            return $this->attributes['guild_id'];
        }

        if (isset($this->attributes['guild']->id)) {
            return $this->attributes['guild']->id;
        }

        return null;
    }

    /**
     * Returns the channel attribute.
     *
     * @return ?Channel The Channel that you have been invited to.
     */
    protected function getChannelAttribute(): ?Channel
    {
        if ($channelId = $this->channel_id) {
            if ($guild = $this->guild) {
                if ($channel = $guild->channels->get('id', $channelId)) {
                    return $channel;
                }
            }

            // @todo potentially slow code
            if ($channel = $this->discord->getChannel($channelId)) {
                return $channel;
            }
        }

        if (isset($this->attributes['channel'])) {
            return $this->factory->part(Channel::class, (array) $this->attributes['channel'] + ['guild_id' => $this->guild_id], true);
        }

        return null;
    }

    /**
     * Returns the channel id attribute.
     *
     * @return string The Channel ID that you have been invited to.
     */
    protected function getChannelIdAttribute(): ?string
    {
        if (isset($this->attributes['channel_id'])) {
            return $this->attributes['channel_id'];
        }

        if (isset($this->attributes['channel']->id)) {
            return $this->attributes['channel']->id;
        }

        return null;
    }

    /**
     * Returns the inviter attribute.
     *
     * @return User|null The User that invited you.
     */
    protected function getInviterAttribute(): ?User
    {
        if (! isset($this->attributes['inviter'])) {
            return null;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['inviter']->id)) {
            return $user;
        }

        return $this->factory->part(User::class, (array) $this->attributes['inviter'], true);
    }

    /**
     * Returns the target user attribute.
     *
     * @return User|null The user whose stream to display for this voice channel stream invite.
     */
    protected function getTargetUserAttribute(): ?User
    {
        if (! isset($this->attributes['target_user'])) {
            return null;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['target_user']->id)) {
            return $user;
        }

        return $this->factory->part(User::class, (array) $this->attributes['target_user'], true);
    }

    /**
     * Returns the target application attribute.
     *
     * @return Application|null The partial target application data.
     */
    protected function getTargetApplicationAttribute(): ?Application
    {
        if (! isset($this->attributes['target_application'])) {
            return null;
        }

        return $this->factory->part(Application::class, (array) $this->attributes['target_application'], true);
    }

    /**
     * Returns the expires at attribute.
     *
     * @return Carbon|null The time that the invite was created.
     *
     * @throws \Exception
     */
    protected function getExpiresAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['expires_at'])) {
            return null;
        }

        return new Carbon($this->attributes['expires_at']);
    }

    /**
     * Returns the guild scheduled event on this invite.
     *
     * @return ScheduledEvent|null The guild scheduled event data.
     */
    protected function getGuildScheduledEventAttribute(): ?ScheduledEvent
    {
        if (! isset($this->attributes['guild_scheduled_event'])) {
            return null;
        }

        if ($guild = $this->guild) {
            if ($scheduled_event = $guild->guild_scheduled_events->get('id', $this->attributes['guild_scheduled_event']->id)) {
                return $scheduled_event;
            }
        }

        return $this->factory->part(ScheduledEvent::class, (array) $this->attributes['guild_scheduled_event'], true);
    }

    /**
     * Returns the created at attribute.
     *
     * @return Carbon|null The time that the invite was created.
     *
     * @throws \Exception
     */
    protected function getCreatedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['created_at'])) {
            return null;
        }

        return new Carbon($this->attributes['created_at']);
    }

    /**
     * Returns the invite URL attribute.
     *
     * @return string The URL to the invite.
     */
    protected function getInviteUrlAttribute(): string
    {
        return 'https://discord.gg/'.$this->code;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'code' => $this->code,
        ];
    }

    public function __toString(): string
    {
        return 'discord.gg/'.$this->code;
    }
}
