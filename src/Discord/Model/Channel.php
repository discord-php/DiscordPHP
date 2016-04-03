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
class Channel extends AbstractModel implements IdentifierModelInterface
{
    /**
     * @var string
     * @Build("id")
     */
    protected $id;

    /**
     * @var Message
     * @Build("last_message_id", class="Discord\Model\Message", isId=true)
     */
    protected $lastMessage;

    /**
     * @var string
     * @Build("name")
     */
    protected $name;

    /**
     * @var array
     * @Build("permission_overwrites", type="array")
     */
    protected $permissionOverwrites;

    /**
     * @var int
     * @Build("position", type="int")
     */
    protected $position;

    /**
     * @var string
     * @Build("topic")
     */
    protected $topic;

    /**
     * @var string
     * @Build("type")
     */
    protected $type;

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
     * @return Channel
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Message
     */
    public function getLastMessage()
    {
        return $this->lastMessage;
    }

    /**
     * @param Message $lastMessage
     *
     * @return Channel
     */
    public function setLastMessage(Message $lastMessage)
    {
        $this->lastMessage = $lastMessage;

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
     * @return Channel
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getPermissionOverwrites()
    {
        return $this->permissionOverwrites;
    }

    /**
     * @param array $permissionOverwrites
     *
     * @return Channel
     */
    public function setPermissionOverwrites($permissionOverwrites)
    {
        $this->permissionOverwrites = $permissionOverwrites;

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
     * @return Channel
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @param string $topic
     *
     * @return Channel
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Channel
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
