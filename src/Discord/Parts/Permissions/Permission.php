<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Permissions;

use Discord\Parts\Part;

/**
 * A Permission defines permissions for a role or user. A Permission can be attached to a channel or role.
 */
abstract class Permission extends Part
{
    /**
     * The default permissions.
     *
     * @var int The default permissions.
     */
    protected $default;

    /**
     * The Bit Offset map.
     *
     * @var array The array of bit offsets.
     */
    protected $bitoffset = [];

    /**
     * {@inheritdoc}
     */
    public $editable = false;

    /**
     * {@inheritdoc}
     */
    public $creatable = false;

    /**
     * {@inheritdoc}
     */
    public $deletable = false;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $attributes = [], $created = false)
    {
        $this->attributes['perms'] = $this->default; // Default perms
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute($key, $value)
    {
        if ($key == 'perms') {
            $this->attributes['perms'] = $value;

            return;
        }

        if (! in_array($key, $this->bitoffset)) {
            return;
        }

        if (! is_bool($value)) {
            return;
        }

        $this->setBitwise($this->bitoffset[$key], $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($key)
    {
        if ($key == 'perms') {
            return $this->attributes['perms'];
        }

        if (! in_array($key, $this->bitoffset)) {
            return;
        }

        if ((($this->perms >> $this->bitoffset[$key]) & 1) == 1) {
            return true;
        }

        return false;
    }

    /**
     * Sets a bitwise attribute.
     *
     * @param int  $key   The bitwise key.
     * @param bool $value The value that is being set.
     *
     * @return bool The value that is being set.
     */
    public function setBitwise($key, $value)
    {
        if ($value) {
            $this->attributes['perms'] |= (1 << $key);
        } else {
            $this->attributes['perms'] &= ~(1 << $key);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicAttributes()
    {
        $return = ['perms' => $this->attributes['perms']];

        foreach ($this->bitoffset as $key => $offset) {
            $return[$key] = $this->getAttribute($key);
        }

        return $return;
    }
}
