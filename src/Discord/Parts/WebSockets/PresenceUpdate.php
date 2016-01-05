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
        return new User((array) $this->attributes['user'], true);
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
