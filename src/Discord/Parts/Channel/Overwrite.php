<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Discord\Parts\Part;
use Discord\Parts\Permissions\ChannelPermission;

/**
 * Overwrite Class.
 *
 * @property string            $id         The unique identifier of the user/role that the overwrite applies to.
 * @property string            $channel_id The unique identifier of the channel that the overwrite belongs to.
 * @property int               $type       The type of part that the overwrite applies to.
 * @property ChannelPermission $allow      The allow permissions.
 * @property ChannelPermission $deny       The deny permissions.
 */
class Overwrite extends Part
{
    public const TYPE_ROLE = 0;
    public const TYPE_MEMBER = 1;

    /**
     * @inheritdoc
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

    /**
     * @inheritDoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'allow' => $this->allow->bitwise,
            'deny' => $this->deny->bitwise,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'overwrite_id' => $this->id,
        ];
    }
}
