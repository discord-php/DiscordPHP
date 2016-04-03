<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Model;

use Discord\Annotation\Build;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class Role extends AbstractModel implements IdentifierModelInterface
{
    /**
     * @var string
     * @Build("id")
     */
    protected $id;

    /**
     * @var int
     * @Build("color", type="int")
     */
    protected $color;

    /**
     * @var bool
     * @Build("hoist", type="bool")
     */
    protected $hoist;

    /**
     * @var bool
     * @Build("managed", type="bool")
     */
    protected $managed;

    /**
     * @var string
     * @Build("name")
     */
    protected $name;

    /**
     * @var int
     * @Build("permissions", type="int")
     */
    protected $permissions;

    /**
     * @var int
     * @Build("position", type="int")
     */
    protected $position;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return Role
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param int $color
     *
     * @return Role
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isHoist()
    {
        return $this->hoist;
    }

    /**
     * @param boolean $hoist
     *
     * @return Role
     */
    public function setHoist($hoist)
    {
        $this->hoist = $hoist;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isManaged()
    {
        return $this->managed;
    }

    /**
     * @param boolean $managed
     *
     * @return Role
     */
    public function setManaged($managed)
    {
        $this->managed = $managed;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Role
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param int $permissions
     *
     * @return Role
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     *
     * @return Role
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }
}
