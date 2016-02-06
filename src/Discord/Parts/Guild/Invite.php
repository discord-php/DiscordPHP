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

class Invite extends Part
{
    /**
     * Is the part editable?
     *
     * @var bool
     */
    public $editable = false;

    /**
     * The parts fillable attributes.
     *
     * @var array
     */
    protected $fillable = ['code', 'max_age', 'guild', 'revoked', 'created_at', 'temporary', 'uses', 'max_uses', 'inviter', 'xkcdpass', 'channel'];

    /**
     * URIs used to get/create/update/delete the part.
     *
     * @var array
     */
    protected $uris = [
        'get' => 'invites/:id',
        'create' => 'channels/:channel_id/invites',
        'delete' => 'invite/:code',
    ];

    /**
     * Returns the invite URL attribute.
     *
     * @return string
     */
    public function getInviteUrlAttribute()
    {
        return "https://discord.gg/{$this->code}";
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild
     */
    public function getGuildAttribute()
    {
        return new Guild((array) $this->attributes['guild'], true);
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel
     */
    public function getChannelAttribute()
    {
        return new Channel((array) $this->attributes['channel'], true);
    }

    /**
     * Returns the channel id attribute.
     *
     * @return int
     */
    public function getChannelIdAttribute()
    {
        return $this->channel->id;
    }

    /**
     * Returns the inviter attribute.
     *
     * @return User
     */
    public function getInviterAttribute()
    {
        return new User((array) $this->attributes['inviter'], true);
    }

    /**
     * Returns the created at attribute.
     *
     * @return Carbon
     */
    public function getCreatedAtAttribute()
    {
        return new Carbon($this->attributes['created_at']);
    }

    /**
     * Returns the attributes needed to create.
     *
     * @return array
     */
    public function getCreatableAttributes()
    {
        return [];
    }
}
