<?php

namespace Discord\Parts\Guild;

use Discord\Parts\Part;
use Discord\Parts\User\User;

class Ban extends Part
{
    /**
     * Is the part editable?
     *
     * @var boolean 
     */
    public $editable = false;

    /**
     * Is the part findable?
     *
     * @var boolean 
     */
    public $findable = false;

    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['user', 'guild'];

    /**
     * URIs used to get/create/update/delete the part.
     *
     * @var array 
     */
    protected $uris = [
        'create'    => 'guilds/:guild_id/bans/:user_id',
        'delete'    => 'guilds/:guild_id/bans/:user_id'
    ];

    /**
     * Returns the guild id attribute.
     *
     * @return integer 
     */
    public function getGuildIdAttribute()
    {
        return $this->guild->id;
    }

    /**
     * Returns the user id attribute.
     *
     * @return integer 
     */
    public function getUserIdAttribute()
    {
        return $this->user->id;
    }

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
        ]);
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

    /**
     * Returns the attributes needed to edit.
     *
     * @return array 
     */
    public function getUpdatableAttributes()
    {
        return [];
    }
}
