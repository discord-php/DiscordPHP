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

use Doctrine\Common\Collections\ArrayCollection;
use Discord\Annotation\Build;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class User extends AbstractModel implements IdentifierModelInterface
{
    /**
     * @var string
     * @Build("user['id']")
     */
    protected $id;

    /**
     * @var string
     * @Build("user['username']")
     */
    protected $username;

    /**
     * @var string
     * @Build("user['discriminator']")
     */
    protected $discriminator;

    /**
     * @var string
     * @Build("user['avatar']")
     */
    protected $avatar;

    /**
     * @var Role[]|ArrayCollection
     * @Build("roles", type="array", class="Discord\Model\Role", isId=true)
     */
    protected $roles;

    /**
     * @var bool
     * @Build("mute", type="bool")
     */
    protected $mute;

    /**
     * @var \DateTime
     * @Build("joined_at", class="DateTime")
     */
    protected $joinedAt;

    /**
     * @var bool
     * @Build("deaf", type="bool")
     */
    protected $deaf;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string|null
     */
    protected $game;

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
     * @return Member
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return Member
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getDiscriminator()
    {
        return $this->discriminator;
    }

    /**
     * @param string $discriminator
     *
     * @return Member
     */
    public function setDiscriminator($discriminator)
    {
        $this->discriminator = $discriminator;

        return $this;
    }

    /**
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param string $avatar
     *
     * @return Member
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return Role[]|ArrayCollection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param Role[]|ArrayCollection $roles
     *
     * @return Member
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMute()
    {
        return $this->mute;
    }

    /**
     * @param boolean $mute
     *
     * @return Member
     */
    public function setMute($mute)
    {
        $this->mute = $mute;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getJoinedAt()
    {
        return $this->joinedAt;
    }

    /**
     * @param \DateTime $joinedAt
     *
     * @return Member
     */
    public function setJoinedAt($joinedAt)
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDeaf()
    {
        return $this->deaf;
    }

    /**
     * @param boolean $deaf
     *
     * @return Member
     */
    public function setDeaf($deaf)
    {
        $this->deaf = $deaf;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return User
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param null|string $game
     *
     * @return User
     */
    public function setGame($game)
    {
        $this->game = $game;

        return $this;
    }
}
