<?php

namespace Discord\Parts\User;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Permission;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\User;

class Member extends Part
{
    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['user', 'roles', 'deaf', 'mute', 'joined_at', 'guild_id'];

    /**
     * Is the part creatable?
     *
     * @var boolean 
     */
    public $creatable = false;

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
     * Returns the id attribute.
     *
     * @return integer 
     */
    public function getIdAttribute()
    {
        return $this->user->id;
    }

    /**
     * Returns the user attribute.
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
            if (in_array($role->id, $this->attributes['roles'])) {
                $roles[] = new Role([
                    'id'            => $role->id,
                    'name'          => $role->name,
                    'color'         => $role->color,
                    'managed'       => $role->managed,
                    'hoist'         => $role->hoist,
                    'position'      => $role->position,
                    'permissions'   => new Permission([
                        'perms' => $role->permissions
                    ]),
                    'guild_id'      => $this->guild_id
                ], true);
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
        $roles = [];

        foreach ($this->roles as $role) {
            $roles[] = $role->id;
        }

        return [
            'roles' => $roles
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
