<?php

namespace Discord\Parts\User;

use Discord\Exceptions\PasswordEmptyException;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\User;

class Client extends Part
{
    /**
     * Is the part creatable?
     *
     * @var boolean 
     */
    public $creatable = false;

    /**
     * Is the part deletable?
     *
     * @var boolean 
     */
    public $deletable = false;

    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['id', 'username', 'password', 'email', 'verified', 'avatar', 'discriminator', 'user_settings'];

    /**
     * URIs used to get/create/update/delete the part.
     *
     * @var array 
     */
    protected $uris = [
        'update'    => 'users/@me'
    ];

    /**
     * Runs any extra construction tasks.
     *
     * @return void 
     */
    public function afterConstruct()
    {
        $this->user = new User([
            'id'            => $this->id,
            'username'      => $this->username,
            'avatar'        => $this->attributes['avatar'],
            'discriminator' => $this->discriminator
        ], true);
    }

    /**
     * Sets the users avatar.
     *
     * @param string $filepath
     * @return boolean
     */
    public function setAvatar($filepath)
    {
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        $file = file_get_contents($filepath);
        $base64 = base64_encode($file);

        $this->attributes['avatarhash'] = "data:image/{$extension};base64,{$base64}";

        return true;
    }

    /**
     * Returns an array of Guilds.
     *
     * @return Collection 
     */
    public function getGuildsAttribute()
    {
        if (isset($this->attributes_cache['guilds'])) {
            return $this->attributes_cache['guilds'];
        }

        $guilds = [];
        $request = Guzzle::get("users/@me/guilds");

        foreach ($request as $index => $guild) {
            $guilds[$index] = new Guild([
                'id'                => $guild->id,
                'name'              => $guild->name,
                'icon'              => $guild->icon,
                'region'            => $guild->region,
                'owner_id'          => $guild->owner_id,
                'roles'             => $guild->roles,
                'joined_at'         => $guild->joined_at,
                'afk_channel_id'    => $guild->afk_channel_id,
                'afk_timeout'       => $guild->afk_timeout,
                'embed_enabled'     => $guild->embed_enabled,
                'embed_channel_id'  => $guild->embed_channel_id
            ], true);
        }

        $guilds = new Collection($guilds);

        $this->attributes_cache['guilds'] = $guilds;

        return $guilds;
    }

    /**
     * Returns the avatar URL for the client.
     * 
     * @return string 
     */
    public function getAvatarAttribute()
    {
        if (empty($this->attributes['avatar'])) {
            return null;
        }
        return "https://discordapp.com/api/users/{$this->id}/avatars/{$this->attributes['avatar']}.jpg";
    }

    /**
     * Returns the avatar ID for the client.
     *
     * @return string 
     */
    public function getAvatarIDAttribute()
    {
        return $this->avatar;
    }

    /**
     * Returns the attributes needed to edit.
     *
     * @return array 
     */
    public function getUpdatableAttributes()
    {
        if (empty($this->attributes['password'])) {
            throw new PasswordEmptyException('You must enter your password to update your profile.');
        }

        $attributes =  [
            'username'      => $this->attributes['username'],
            'email'         => $this->email,
            'password'      => $this->attributes['password'],
            'avatar'        => $this->attributes['avatarhash']
        ];

        if (!empty($this->attributes['new_password'])) {
            $attributes['new_password'] = $this->attributes['new_password'];
        }

        return $attributes;
    }
}
