<?php

namespace Discord\Parts\Guild;

use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Permission;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

class Guild extends Part
{
    const REGION_DEFAULT = self::REGION_US_WEST;
    const REGION_US_WEST = 'us-west';
    const REGION_US_EAST = 'us-east';
    const REGION_SINGAPORE = 'singapore';
    const REGION_LONDON = 'london';
    const REGION_SYDNEY = 'sydney';
    const REGION_AMSTERDAM = 'amsterdam';

    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['id', 'name', 'icon', 'region', 'owner_id', 'roles', 'joined_at', 'afk_channel_id', 'afk_timeout', 'embed_enabled', 'embed_channel_id', 'features', 'splash', 'emojis', 'large'];

    /**
     * URIs used to get/create/update/delete the part.
     *
     * @var array 
     */
    protected $uris = [
        'get'       => 'guilds/:id',
        'create'    => 'guilds',
        'update'    => 'guilds/:id',
        'delete'    => 'guilds/:id'
    ];

    /**
     * An array of valid regions.
     *
     * @var array 
     */
    protected $regions = [
        self::REGION_US_WEST,
        self::REGION_US_EAST,
        self::REGION_LONDON,
        self::REGION_SINGAPORE,
        self::REGION_SYDNEY,
        self::REGION_AMSTERDAM
    ];

    /**
     * Alias for delete().
     *
     * @return boolean 
     */
    public function leave()
    {
        return $this->delete();
    }

    /**
     * Returns the guilds members.
     *
     * @return Collection
     */
    public function getMembersAttribute()
    {
        if (isset($this->attributes_cache['members'])) {
            return $this->attributes_cache['members'];
        }

        // Members aren't retrievable via REST anymore,
        // they will be set if the websocket is used.

        return null;
    }

    /**
     * Returns the guilds roles.
     *
     * @return Collection
     */
    public function getRolesAttribute()
    {
        if (isset($this->attributes_cache['roles'])) {
            return $this->attributes_cache['roles'];
        }

        $roles = [];

        foreach ($this->attributes['roles'] as $index => $role) {
            $roles[$index] = new Role([
                'id'            => $role->id,
                'name'          => $role->name,
                'color'         => $role->color,
                'managed'       => $role->managed,
                'hoist'         => $role->hoist,
                'position'      => $role->position,
                'permissions'   => new Permission([
                    'perms' => $role->permissions
                ]),
                'guild_id'      => $this->id
            ], true);
        }

        $roles = new Collection($roles);

        $this->attributes_cache['roles'] = $roles;

        return $roles;
    }

    /**
     * Returns the owner.
     *
     * @return User 
     */
    public function getOwnerAttribute()
    {
        if (isset($this->attributes_cache['owner'])) {
            return $this->attributes_cache['owner'];
        }

        $request = Guzzle::get($this->replaceWithVariables('users/:owner_id'));

        $owner = new User([
            'id'            => $request->id,
            'username'      => $request->username,
            'avatar'        => $request->avatar,
            'discriminator' => $request->discriminator
        ], true);

        $this->attributes_cache['owner'] = $owner;

        return $owner;
    }

    /**
     * Returns the guilds channels.
     *
     * @return Collection 
     */
    public function getChannelsAttribute()
    {
        if (isset($this->attributes_cache['channels'])) {
            return $this->attributes_cache['channels'];
        }
    
        $channels = [];
        $request = Guzzle::get($this->replaceWithVariables('guilds/:id/channels'));

        foreach ($request as $index => $channel) {
            $channels[$index] = new Channel([
                'id'                    => $channel->id,
                'name'                  => $channel->name,
                'type'                  => $channel->type,
                'topic'                 => $channel->topic,
                'guild_id'              => $channel->guild_id,
                'position'              => $channel->position,
                'is_private'            => $channel->is_private,
                'last_message_id'       => $channel->last_message_id,
                'permission_overwrites' => $channel->permission_overwrites
            ], true);
        }

        $channels = new Collection($channels);

        $this->attributes_cache['channels'] = $channels;

        return $channels;
    }

    /**
     * Returns the guilds bans.
     *
     * @return Collection 
     */
    public function getBansAttribute()
    {
        if (isset($this->attributes_cache['bans'])) {
            return $this->attributes_cache['bans'];
        }

        $bans = [];
        
        try {
            $request = Guzzle::get($this->replaceWithVariables('guilds/:id/bans'));
        } catch (DiscordRequestFailedException $e) {
            return false;
        }

        foreach ($request as $index => $ban) {
            $bans[$index] = new Ban([
                'user'  => $ban->user,
                'guild' => $this
            ], true);
        }

        $bans = new Collection($bans);

        $this->attributes_cache['bans'] = $bans;

        return $bans;
    }

    /**
     * Returns the guilds icon.
     *
     * @return string|null
     */
    public function getIconAttribute()
    {
        if (is_null($this->attributes['icon'])) {
            return null;
        }

        return "https://discordapp.com/{$this->attributes['id']}/icons/{$this->attributes['icon']}.jpg";
    }

    /**
     * Returns the guild icon hash.
     *
     * @return string|null
     */
    public function getIconHashAttribute()
    {
        return $this->attributes['icon'];
    }

    /**
     * Validates the specified region.
     *
     * @return string 
     */
    public function validateRegion()
    {
        if (!in_array($this->region, $this->regions)) {
            return self::REGION_DEFUALT;
        }

        return $this->region;
    }

    /**
     * Returns the attributes needed to create.
     *
     * @return array 
     */
    public function getCreatableAttributes()
    {
        return [
            'name'      => $this->name,
            'region'    => $this->validateRegion()
        ];
    }

    /**
     * Returns the attributes needed to edit.
     *
     * @return array 
     */
    public function getUpdatableAttributes()
    {
        return [
            'name' => $this->name
        ];
    }
}
