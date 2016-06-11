<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Discord\Parts\Part;
use Discord\Parts\Permissions\ChannelPermission;

/**
 * Overwrite Class.
 *
 * @property string $id
 * @property string $channel_id
 * @property string $type
 * @property bool   $allow
 * @property bool   $deny
 */
class Overwrite extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'channel_id', 'type', 'allow', 'deny', 'permissions'];

    /**
     * {@inheritdoc}
     */
    public function afterConstruct()
    {
        $this->permissions = $this->factory->create(ChannelPermission::class);
        $this->permissions->decodeBitwise($this->allow, $this->deny);
    }

    /**
     * Sets the permissions attribute.
     *
     * @param ChannelPermission $permissions Permission object.
     *
     * @return void 
     */
    public function setPermissionsAttribute($permissions)
    {
        if (! ($permissions instanceof ChannelPermission)) {
            return;
        }

        list($allow, $deny) = $permissions->bitwise;
        $this->allow = $allow;
        $this->deny = $deny;

        $this->attributes['permissions'] = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        return [];
    }
}
