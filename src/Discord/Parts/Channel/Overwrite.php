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

class Overwrite extends Part
{
    /**
     * {@inheritdoc}
     */
    public $findable = false;

    /**
     * {@inheritdoc}
     */
    public $creatable = false;

    /**
     * {@inheritdoc}
     */
    public $editable = false;

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'channel_id', 'type', 'allow', 'deny'];

    /**
     * {@inheritdoc}
     */
    protected $uris = [
        'delete' => 'channels/:channel_id/permissions/:id',
    ];

    /**
     * Returns the allow attribute.
     *
     * @return ChannelPermission The allow attribute.
     */
    public function getAllowAttribute()
    {
        $perm = $this->partFactory->create(ChannelPermission::class);
        $perm->perms = $this->attributes['allow'];

        return $perm;
    }

    /**
     * Returns the deny attribute.
     *
     * @return ChannelPermission The deny attribute.
     */
    public function getDenyAttribute()
    {
        $perm = $this->partFactory->create(ChannelPermission::class);
        $perm->perms = $this->attributes['deny'];

        return $perm;
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
