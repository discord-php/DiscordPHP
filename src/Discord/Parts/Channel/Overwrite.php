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
 * @property ChannelPermission                            $allow       The allow permissions.
 * @property ChannelPermission                            $deny        The deny permissions.
 */
class Overwrite extends Part
{
    const TYPE_MEMBER = 'member';
    const TYPE_ROLE = 'role';

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'channel_id', 'type', 'allow', 'deny', 'permissions'];

    /**
     * Sets the allow attribute of the role.
     *
     * @param  ChannelPermission|int $allow
     * @throws \Exception
     */
    protected function setAllowAttribute($allow): void
    {
        if (! ($allow instanceof ChannelPermission)) {
            $allow = $this->factory->create(ChannelPermission::class, ['bitwise' => $allow], true);
        }

        $this->attributes['allow'] = $allow;
    }

    /**
     * Sets the deny attribute of the role.
     *
     * @param  ChannelPermission|int $deny
     * @throws \Exception
     */
    protected function setDenyAttribute($deny): void
    {
        if (! ($deny instanceof ChannelPermission)) {
            $deny = $this->factory->create(ChannelPermission::class, ['bitwise' => $deny], true);
        }

        $this->attributes['deny'] = $deny;
    }
}
