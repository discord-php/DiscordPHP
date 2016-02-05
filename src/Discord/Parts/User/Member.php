<?php

namespace Discord\Parts\User;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Permissions\RolePermission as Permission;
use Discord\Parts\User\User;

class Member extends Part
{
    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['user', 'roles', 'deaf', 'mute', 'joined_at', 'guild_id', 'status', 'game'];

    /**
     * Is the part creatable?
     *
     * @var boolean 
     */
    public $creatable = false;

    /**
     * Is the part findable?
     *
     * @var boolean 
     */
    public $findable = false;

    /**
     * Should we fill the part after saving?
     *
     * @var boolean 
     */
    protected $fillAfterSave = false;

    /**
     * URIs used to get/create/update/delete the part.
     *
     * @var array 
     */
    protected $uris = [
        'get'       => '',
        'create'    => '',
        'update'    => 'guilds/:guild_id/members/:id',
        'delete'    => 'guilds/:guild_id/members/:id'
    ];

    /**
     * Alias for delete.
     *
     * @return boolean 
     */
    public function kick()
    {
        return $this->delete();
    }

    /**
     * Moves the member to another voice channel.
     *
     * @param Channel|int $channel 
     * @return boolean 
     */
    public function moveMember($channel)
    {
        if ($channel instanceof Channel) {
            $channel = $channel->id;
        }

        Guzzle::patch("guilds/{$this->guild_id}/members/{$this->id}", [
            'channel_id' => $channel
        ]);

        // At the moment we are unable to check if the member
        // was moved successfully.

        return true;
    }

    /**
     * Adds a role to the member.
     *
     * @param Role|int $role 
     * @return boolean 
     */
    public function addRole($role)
    {
        if (is_int($role)) {
            $role = new Role(['id' => $role], true);
        }

        // We don't want a double up on roles
        if (false !== array_search($role->id, (array) $this->attributes['roles'])) {
            return false;
        }

        // Preload the roles if there is no collection
        $this->getRolesAttribute();

        $this->attributes['roles'][] = $role->id;
        $this->attributes_cache['roles']->push($role);

        return true;
    }

    /**
     * Removes a role from the user.
     *
     * @param Role|int $role 
     * @return boolean 
     */
    public function removeRole($role)
    {
        if ($role instanceof Role) {
            $role = $role->id;
        }

        if (false !== $index = array_search($role, $this->attributes['roles'])) {
            unset($this->attributes['roles'][$index]);
        }

        $rolePart = $this->roles->get('id', $role);

        if (false !== $index = array_search($rolePart, $this->roles->all())) {
            $this->roles->pull($index);
        }

        return true;
    }

    /**
     * Returns the id attribute.
     *
     * @return integer 
     */
    public function getIdAttribute()
    {
        return $this->user->id;
    }

    /**
     * Returns the username attribute.
     *
     * @return string 
     */
    public function getUserameAttribute()
    {
        return $this->user->username;
    }

    /**
     * Returns the user attribute.
     *
     * @return User
     */
    public function getUserAttribute()
    {
        return new User((array) $this->attributes['user'], true);
    }

    /**
     * Returns the roles attribute.
     *
     * @return Collection 
     */
    public function getRolesAttribute()
    {
        if (isset($this->attributes_cache['roles'])) {
            return $this->attributes_cache['roles'];
        }

        $roles = [];
        $request = Guzzle::get($this->replaceWithVariables('guilds/:guild_id/roles'));

        foreach ($request as $key => $role) {
            if (!(false === array_search($role->id, (array) $this->attributes['roles']))) {
                $perm = new Permission([
                    'perms' => $role->permissions
                ]);
                $role = (array) $role;
                $role['permissions'] = $perm;
                $roles[] = new Role($role, true);
            }
        }

        $roles = new Collection($roles);

        $this->attributes_cache['roles'] = $roles;

        return $roles;
    }

    /**
     * Returns the joined at attribute.
     *
     * @return Carbon 
     */
    public function getJoinedAtAttribute()
    {
        return new Carbon($this->attributes['joined_at']);
    }

    /**
     * Returns the attributes needed to edit.
     *
     * @return array 
     */
    public function getUpdatableAttributes()
    {
        return [
            'roles' => $this->attributes['roles']
        ];
    }

    /**
     * Returns a formatted mention.
     *
     * @return string 
     */
    public function __toString()
    {
        return "<@{$this->user->id}>";
    }
}
