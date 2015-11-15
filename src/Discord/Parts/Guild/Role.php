<?php

namespace Discord\Parts\Guild;

use Discord\Parts\Part;

class Role extends Part
{
    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['id', 'name', 'color', 'managed', 'hoist', 'position', 'permissions'];

    /**
     * URIs used to get/create/update/delete the part.
     *
     * @var array 
     */
    protected $uris = [
        'get'       => '',
        'create'    => 'guilds/:guild_id/roles',
        'update'    => 'guilds/:id',
        'delete'    => 'guilds/:guild_id/roles/:id'
    ];

    /**
     * Colors available to be applied to a role.
     *
     * @var array 
     */
    public $colors = [
        
    ];

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
        return [
            'name'          => $this->name,
            'hoist'         => $this->hoist,
            'color'         => $this->color,
            'permissions'   => $this->permissions
        ];
    }
}
