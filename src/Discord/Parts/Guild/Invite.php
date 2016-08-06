<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Carbon\Carbon;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\Deferred;

/**
 * An invite to a Channel and Guild.
 *
 * @property string                         $code       The invite code.
 * @property int                            $max_age    How many seconds the invite will be alive.
 * @property \Discord\Parts\Guild\Guild     $guild      The guild that the invite is for.
 * @property bool                           $revoked    Whether the invite has been revoked.
 * @property Carbon                         $created_at A timestamp of when the invite was created.
 * @property bool                           $temporary  Whether the invite is for temporary membership.
 * @property int                            $uses       How many times the invite has been used.
 * @property int                            $max_uses   How many times the invite can be used.
 * @property \Discord\Parts\User\User       $inviter    The user that created the invite.
 * @property \Discord\Parts\Channel\Channel $channel    The channel that the invite is for.
 */
class Invite extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'code',
        'max_age',
        'guild',
        'revoked',
        'created_at',
        'temporary',
        'uses',
        'max_uses',
        'inviter',
        'channel',
    ];

    /**
     * Accepts the invite.
     *
     * @return \React\Promise\Promise
     */
    public function accept()
    {
        $deferred = new Deferred();

        if ($this->revoked) {
            $deferred->reject(new \Exception('This invite has been revoked.'));

            return $deferred->promise();
        }

        if ($this->uses >= $this->max_uses) {
            $deferred->reject(new \Exception('This invite has been used the max times.'));

            return $deferred->promise();
        }

        $this->http->post("invite/{$this->code}")->then(
            \React\Partial\bind_right($this->resolve, $deferred),
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Returns the id attribute.
     *
     * @return string The id attribute.
     */
    public function getIdAttribute()
    {
        return $this->code;
    }

    /**
     * Returns the invite URL attribute.
     *
     * @return string The URL to the invite.
     */
    public function getInviteUrlAttribute()
    {
        return "https://discord.gg/{$this->code}";
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild The Guild that you have been invited to.
     */
    public function getGuildAttribute()
    {
        return $this->factory->create(Guild::class, (array) $this->attributes['guild'], true);
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel The Channel that you have been invited to.
     */
    public function getChannelAttribute()
    {
        return $this->factory->create(Channel::class, (array) $this->attributes['channel'], true);
    }

    /**
     * Returns the channel id attribute.
     *
     * @return int The Channel ID that you have been invited to.
     */
    public function getChannelIdAttribute()
    {
        return $this->channel->id;
    }

    /**
     * Returns the inviter attribute.
     *
     * @return User The User that invited you.
     */
    public function getInviterAttribute()
    {
        return $this->factory->create(User::class, (array) $this->attributes['inviter'], true);
    }

    /**
     * Returns the created at attribute.
     *
     * @return Carbon The time that the invite was created.
     */
    public function getCreatedAtAttribute()
    {
        return new Carbon($this->attributes['created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes()
    {
        return [];
    }
}
