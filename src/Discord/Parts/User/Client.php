<?php

namespace Discord\Parts\User;

use Discord\Helpers\Guzzle;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\User;

class Client extends Part
{
    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['id', 'username', 'email', 'verified', 'avatar', 'discriminator'];

    /**
     * Runs any extra construction tasks
     *
     * @return void 
     */
    public function afterConstruct()
    {
        $this->user = new User([
            'id'            => $this->id,
            'username'        => $this->username,
            'avatar'        => $this->avatar,
            'discriminator'    => $this->discriminator
        ], true);
    }

    /**
     * Returns an array of Guilds.
     *
     * @return array 
     */
    public function getGuildsAttribute()
    {
        if (isset($this->attributes_cache['guilds'])) {
            return $this->attributes_cache['guilds'];
        }

        $guilds = [];
        $request = Guzzle::get("users/{$this->id}/guilds");

        foreach ($request as $index => $guild) {
            $guilds[$index] = new Guild([
                'id'                => $guild->id,
                'name'                => $guild->name,
                'icon'                => $guild->icon,
                'region'            => $guild->region,
                'owner_id'            => $guild->owner_id,
                'roles'                => $guild->roles,
                'joined_at'            => $guild->joined_at,
                'afk_channel_id'    => $guild->afk_channel_id,
                'afk_timeout'        => $guild->afk_timeout,
                'embed_enabled'        => $guild->embed_enabled,
                'embed_channel_id'    => $guild->embed_channel_id
            ], true);
        }

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
}
