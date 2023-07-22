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
 * Channel Overwrite Class.
 *
 * @link https://discord.com/developers/docs/resources/channel#overwrite-object
 *
 * @since 3.1.1
 *
 * @property string            $id    The unique identifier of the user/role that the overwrite applies to.
 * @property int               $type  The type of part that the overwrite applies to.
 * @property ChannelPermission $allow The allow permissions.
 * @property ChannelPermission $deny  The deny permissions.
 *
 * @property string $channel_id The unique identifier of the channel that the overwrite belongs to.
 */
class Overwrite extends Part
{
    public const TYPE_ROLE = 0;
    public const TYPE_MEMBER = 1;

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'type',
        'allow',
        'deny',

        // @internal
        'channel_id',
    ];

    /**
     * Sets the allow attribute of the role.
     *
     * @param ChannelPermission|int $allow
     */
    protected function setAllowAttribute($allow): void
    {
        if (! ($allow instanceof ChannelPermission)) {
            $allow = $this->createOf(ChannelPermission::class, ['bitwise' => $allow]);
        }

        $this->attributes['allow'] = $allow;
    }

    /**
     * Sets the deny attribute of the role.
     *
     * @param ChannelPermission|int $deny
     */
    protected function setDenyAttribute($deny): void
    {
        if (! ($deny instanceof ChannelPermission)) {
            $deny = $this->createOf(ChannelPermission::class, ['bitwise' => $deny]);
        }

        $this->attributes['deny'] = $deny;
    }

    /**
     * {@inheritDoc}
     *
     * @see Channel::getUpdatableAttributes()
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
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'overwrite_id' => $this->id,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRawAttributes(): array
    {
        $attributes = $this->attributes;
        $attributes['allow'] = $this->attributes['allow']->bitwise;
        $attributes['deny'] = $this->attributes['deny']->bitwise;

        return $attributes;
    }
}
