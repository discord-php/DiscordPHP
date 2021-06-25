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
use Discord\Http\Endpoint;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\ExtendedPromiseInterface;

/**
 * An invite to a Channel and Guild.
 *
 * @property string  $code       The invite code.
 * @property int     $max_age    How many seconds the invite will be alive.
 * @property Guild   $guild      The guild that the invite is for.
 * @property string  $guild_id
 * @property bool    $revoked    Whether the invite has been revoked.
 * @property Carbon  $created_at A timestamp of when the invite was created.
 * @property bool    $temporary  Whether the invite is for temporary membership.
 * @property int     $uses       How many times the invite has been used.
 * @property int     $max_uses   How many times the invite can be used.
 * @property User    $inviter    The user that created the invite.
 * @property Channel $channel    The channel that the invite is for.
 * @property string  $channel_id
 */
class Invite extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'code',
        'max_age',
        'guild',
        'guild_id',
        'revoked',
        'created_at',
        'temporary',
        'uses',
        'max_uses',
        'inviter',
        'channel',
        'channel_id',
    ];

    /**
     * Accepts the invite.
     *
     * @return ExtendedPromiseInterface
     */
    public function accept(): ExtendedPromiseInterface
    {
        if ($this->revoked) {
            return \React\Promise\reject(new \Exception('This invite has been revoked.'));
        }

        if ($this->uses >= $this->max_uses) {
            return \React\Promise\reject(new \Exception('This invite has been used the max times.'));
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
     * @return Guild      The Guild that you have been invited to.
     * @throws \Exception
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (isset($this->attributes['guild_id']) && $guild = $this->discord->guilds->get('id', $this->attributes['guild_id'])) {
            return $guild;
        }

        return $this->factory->create(Guild::class, $this->attributes['guild'] ?? [], true);
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
     * @return Channel    The Channel that you have been invited to.
     * @throws \Exception
     */
    protected function getChannelAttribute(): ?Channel
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
     * @return User       The User that invited you.
     * @throws \Exception
     */
    protected function getInviterAttribute(): User
    {
        if (isset($this->attributes['inviter']) && $user = $this->discord->users->get('id', $this->attributes['inviter']->id ?? null)) {
            return $user;
        }

        return $this->factory->create(User::class, $this->attributes['inviter'], true);
    }

    /**
     * Returns the created at attribute.
     *
     * @return Carbon     The time that the invite was created.
     * @throws \Exception
     */
    protected function getCreatedAtAttribute(): Carbon
    {
        return new Carbon($this->attributes['created_at']);
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        return [];
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
