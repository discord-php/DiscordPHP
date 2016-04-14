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
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * An invite to a Channel and Guild.
 *
 * @property string  $code
 * @property int     $max_age
 * @property Guild   $guild
 * @property bool    $revoked
 * @property Carbon  $created_at
 * @property bool    $temporary
 * @property int     $uses
 * @property int     $max_uses
 * @property Member  $inviter
 * @property bool    $xkcdpass
 * @property Channel $channel
 */
class Invite extends Part
{
    /**
     * {@inheritdoc}
     */
    public $editable = false;

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
        'xkcdpass',
        'channel',
    ];

    /**
     * {@inheritdoc}
     */
    protected $uris = [
        'get'    => 'invites/:code',
        'create' => 'channels/:channel_id/invites',
        'delete' => 'invite/:code',
    ];

    /**
     * Accepts the invite.
     *
     * @return bool Whether the accept succeeded.
     */
    public function accept()
    {
        if ($this->revoked) {
            return false;
        }

        if ($this->uses >= $this->max_uses) {
            return false;
        }

        try {
            Guzzle::post("invite/{$this->code}");
        } catch (\Exception $e) {
            return false;
        }

        return true;
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
        return new Guild((array) $this->attributes['guild'], true);
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel The Channel that you have been invited to.
     */
    public function getChannelAttribute()
    {
        return new Channel((array) $this->attributes['channel'], true);
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
        return new User((array) $this->attributes['inviter'], true);
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
