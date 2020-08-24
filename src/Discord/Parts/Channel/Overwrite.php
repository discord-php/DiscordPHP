<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
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
 * @property string                                       $id          The unique identifier of the user/role that the overwrite applies to.
 * @property string                                       $channel_id  The unique identifier of the channel that the overwrite belongs to.
 * @property string                                       $type        The type of part that the overwrite applies to. Can be 'role' or 'user'.
 * @property int                                          $allow       The bitwise integer for allow permissions.
 * @property int                                          $deny        The bitwise integer for deny permissions.
 * @property \Discord\Parts\Permissions\ChannelPermission $permissions The overwrite permissions.
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
