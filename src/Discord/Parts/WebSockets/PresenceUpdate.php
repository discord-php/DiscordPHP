<?php

namespace Discord\Parts\WebSockets;

use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\User;

class PresenceUpdate extends Part
{
    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['user', 'roles', 'guild_id', 'status', 'game'];

    /**
     * Gets the user attribute.
     *
     * @return User 
     */
    public function getUserAttribute()
    {
        return new User([
            'id'            => $this->attributes['user']->id,
            'username'      => $this->attributes['user']->username,
            'avatar'        => $this->attributes['user']->avatar,
            'discriminator' => $this->attributes['user']->discriminator
        ], true);
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild 
     */
    public function getGuildAttribute()
    {
        return new Guild([
            'id'    => $this->guild_id
        ], true);
    }
}
