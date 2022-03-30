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
use Discord\Http\Endpoint;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\ExtendedPromiseInterface;

/**
 * An invite to a Channel and Guild.
 *
 * @see https://discord.com/developers/docs/resources/invite
 *
 * @property string              $code                       The invite code.
 * @property Guild|null          $guild                      The guild that the invite is for.
 * @property string|null         $guild_id
 * @property Channel             $channel                    The channel that the invite is for.
 * @property string|null         $channel_id
 * @property User|null           $inviter                    The user that created the invite.
 * @property int|null            $target_type                The type of target for this voice channel invite.
 * @property User|null           $target_user                The user whose stream to display for this voice channel stream invite.
 * @property object|null         $target_application         The embedded application to open for this voice channel embedded application invite.
 * @property int|null            $approximate_presence_count Approximate count of online members, returned from the GET /invites/<code> endpoint when with_counts is true.
 * @property int|null            $approximate_member_count   Approximate count of total members, returned from the GET /invites/<code> endpoint when with_counts is true.
 * @property Carbon|null         $expires_at                 The expiration date of this invite, returned from the GET /invites/<code> endpoint when with_expiration is true.
 * @property ScheduledEvent|null $guild_scheduled_event      Guild scheduled event data, only included if guild_scheduled_event_id contains a valid guild scheduled event id.
 * @property int                 $uses                       How many times the invite has been used.
 * @property int                 $max_uses                   How many times the invite can be used.
 * @property int                 $max_age                    How many seconds the invite will be alive.
 * @property bool                $temporary                  Whether the invite is for temporary membership.
 * @property Carbon              $created_at                 A timestamp of when the invite was created.
 */
class Invite extends Part
{
    /**
     * @inheritdoc
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
        'stage_instance', // deprecated
        'guild_scheduled_event',

        // Extra metadata
        'uses',
        'max_uses',
        'max_age',
        'temporary',
        'created_at',

        // Internal use
        'guild_id',
        'channel_id',
    ];

    public const TARGET_TYPE_STREAM = 1;
    public const TARGET_TYPE_EMBEDDED_APPLICATION = 2;

    /**
     * Accepts the invite.
     *
     * @deprecated 7.0.6
     *
     * @return ExtendedPromiseInterface
     */
    public function accept(): ExtendedPromiseInterface
    {
        if ($this->uses >= $this->max_uses) {
            return \React\Promise\reject(new \RuntimeException('This invite has been used the max times.'));
        }

        return $this->http->post(Endpoint::bind(Endpoint::INVITE, $this->code));
    }

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
     * Returns the invite URL attribute.
     *
     * @return string The URL to the invite.
     */
    protected function getInviteUrlAttribute(): string
    {
        return "https://discord.gg/{$this->code}";
    }

    /**
     * Returns the guild attribute.
     *
     * @throws \Exception
     *
     * @return Guild|null The Guild that you have been invited to.
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (isset($this->attributes['guild_id']) && $guild = $this->discord->guilds->get('id', $this->attributes['guild_id'])) {
            return $guild;
        }

        if (! isset($this->attributes['guild'])) {
            return null;
        }

        if ($guild = $this->discord->guilds->get('id', $this->attributes['guild']->id)) {
            return $guild;
        }

        return $this->factory->create(Guild::class, $this->attributes['guild'], true);
    }

    /**
     * Returns the guild id attribute.
     *
     * @return string
     */
    protected function getGuildIdAttribute(): ?string
    {
        if (isset($this->attributes['guild_id'])) {
            return $this->attributes['guild_id'];
        }

        return $this->guild->id;
    }

    /**
     * Returns the channel attribute.
     *
     * @throws \Exception
     *
     * @return Channel The Channel that you have been invited to.
     */
    protected function getChannelAttribute(): Channel
    {
        if (isset($this->attributes['channel_id']) && $channel = $this->discord->getChannel($this->attributes['channel_id'])) {
            return $channel;
        }

        return $this->factory->create(Channel::class, $this->attributes['channel'] ?? [], true);
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

        return $this->channel->id;
    }

    /**
     * Returns the inviter attribute.
     *
     * @throws \Exception
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

        return $this->factory->create(User::class, $this->attributes['inviter'], true);
    }

    /**
     * Returns the created at attribute.
     *
     * @throws \Exception
     *
     * @return Carbon The time that the invite was created.
     */
    protected function getCreatedAtAttribute(): Carbon
    {
        return new Carbon($this->attributes['created_at']);
    }

    /**
     * Returns the target user attribute.
     *
     * @throws \Exception
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

        return $this->factory->create(User::class, $this->attributes['target_user'], true);
    }

    /**
     * Returns the expires at attribute.
     *
     * @throws \Exception
     *
     * @return Carbon|null The time that the invite was created.
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

        if ($this->guild && $scheduled_event = $this->guild->guild_scheduled_events->get('id', $this->attributes['guild_scheduled_event']->id)) {
            return $scheduled_event;
        }

        return $this->factory->create(ScheduledEvent::class, $this->attributes['guild_scheduled_event'], true);
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'code' => $this->code,
        ];
    }
}
