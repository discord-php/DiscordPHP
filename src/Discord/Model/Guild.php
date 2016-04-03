<?php

/**
 * This file is part of DiscordPHP
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Discord\Annotation\Build;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class Guild extends AbstractModel implements IdentifierModelInterface
{
    /**
     * Regions
     */
    const REGION_DEFAULT    = self::REGION_US_WEST;
    const REGION_US_WEST    = 'us-west';
    const REGION_US_SOUTH   = 'us-south';
    const REGION_US_EAST    = 'us-east';
    const REGION_US_CENTRAL = 'us-central';
    const REGION_SINGAPORE = 'singapore';
    const REGION_LONDON    = 'london';
    const REGION_SYDNEY    = 'sydney';
    const REGION_FRANKFURT = 'frankfurt';
    const REGION_AMSTERDAM = 'amsterdam';

    /**
     * Verification Levels
     */
    const LEVEL_OFF       = 0;
    const LEVEL_LOW       = 1;
    const LEVEL_MEDIUM    = 2;
    const LEVEL_TABLEFLIP = 3;

    /**
     * @var string
     * @Build("id")
     */
    protected $id;

    /**
     * @var string
     * @Build("name")
     */
    protected $name;

    /**
     * @var string
     * @Build("icon")
     */
    protected $icon;

    /**
     * @var string
     * @Build("region")
     */
    protected $region;

    /**
     * @var Role[]|ArrayCollection
     * @Build("roles", class="Discord\Model\Role", type="array")
     */
    protected $roles;

    /**
     * @var \DateTime
     * @Build("joined_at", class="DateTime")
     */
    protected $joinedAt;

    /**
     * @var int
     * @Build("afk_timeout", type="int")
     */
    protected $afkTimeout;

    /**
     * @var bool
     * @Build("embed_enabled", type="bool")
     */
    protected $embedEnabled;

    /**
     * @var mixed
     * @Build("features", type="array")
     */
    protected $features;

    /**
     * @var mixed
     * @Build("splash")
     */
    protected $splash;

    /**
     * @var mixed
     * @Build("emojis", type="array")
     */
    protected $emojis;

    /**
     * @var mixed
     * @Build("large")
     */
    protected $large;

    /**
     * @var int
     * @Build("verification_level", type="int")
     */
    protected $verificationLevel;

    /**
     * @var int
     * @Build("member_count", type="int")
     */
    protected $memberCount;

    /**
     * @var User[]|ArrayCollection
     * @Build("members", class="Discord\Model\User", type="array")
     */
    protected $members;

    /**
     * @var Channel[]|ArrayCollection
     * @Build("channels", class="Discord\Model\Channel", type="array")
     */
    protected $channels;

    /**
     * @var User
     * @Build("owner_id", class="Discord\Model\User", isId=true)
     */
    protected $owner;

    /**
     * @var Channel
     * @Build("embed_channel_id", class="Discord\Model\Channel", isId=true)
     */
    protected $embedChannel;

    /**
     * @var Channel
     * @Build("afk_channel_id", class="Discord\Model\Channel", isId=true)
     */
    protected $afkChannel;

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
     * @return Guild
     */
    public function setId($id)
    {
        $this->id = $id;

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
     * @return Guild
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     *
     * @return Guild
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param string $region
     *
     * @return Guild
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     *
     * @return Guild
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

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
     * @return Guild
     */
    public function setRoles(array $roles)
    {
        $this->roles = new ArrayCollection($roles);

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
     * @return Guild
     */
    public function setJoinedAt($joinedAt)
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    /**
     * @return Channel
     */
    public function getAfkChannel()
    {
        return $this->afkChannel;
    }

    /**
     * @param Channel $afkChannel
     *
     * @return Guild
     */
    public function setAfkChannel($afkChannel)
    {
        $this->afkChannel = $afkChannel;

        return $this;
    }

    /**
     * @return int
     */
    public function getAfkTimeout()
    {
        return $this->afkTimeout;
    }

    /**
     * @param int $afkTimeout
     *
     * @return Guild
     */
    public function setAfkTimeout($afkTimeout)
    {
        $this->afkTimeout = $afkTimeout;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEmbedEnabled()
    {
        return $this->embedEnabled;
    }

    /**
     * @param boolean $embedEnabled
     *
     * @return Guild
     */
    public function setEmbedEnabled($embedEnabled)
    {
        $this->embedEnabled = $embedEnabled;

        return $this;
    }

    /**
     * @return Channel
     */
    public function getEmbedChannel()
    {
        return $this->embedChannel;
    }

    /**
     * @param Channel $embedChannel
     *
     * @return Guild
     */
    public function setEmbedChannel($embedChannel)
    {
        $this->embedChannel = $embedChannel;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * @param mixed $features
     *
     * @return Guild
     */
    public function setFeatures($features)
    {
        $this->features = $features;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSplash()
    {
        return $this->splash;
    }

    /**
     * @param mixed $splash
     *
     * @return Guild
     */
    public function setSplash($splash)
    {
        $this->splash = $splash;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEmojis()
    {
        return $this->emojis;
    }

    /**
     * @param mixed $emojis
     *
     * @return Guild
     */
    public function setEmojis($emojis)
    {
        $this->emojis = $emojis;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLarge()
    {
        return $this->large;
    }

    /**
     * @param mixed $large
     *
     * @return Guild
     */
    public function setLarge($large)
    {
        $this->large = $large;

        return $this;
    }

    /**
     * @return int
     */
    public function getVerificationLevel()
    {
        return $this->verificationLevel;
    }

    /**
     * @param int $verificationLevel
     *
     * @return Guild
     */
    public function setVerificationLevel($verificationLevel)
    {
        $this->verificationLevel = $verificationLevel;

        return $this;
    }

    /**
     * @return int
     */
    public function getMemberCount()
    {
        return $this->memberCount;
    }

    /**
     * @param int $memberCount
     *
     * @return Guild
     */
    public function setMemberCount($memberCount)
    {
        $this->memberCount = $memberCount;

        return $this;
    }

    /**
     * @return User[]|ArrayCollection
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @param User[]|ArrayCollection $members
     *
     * @return Guild
     */
    public function setMembers($members)
    {
        $this->members = $members;

        return $this;
    }

    /**
     * @return Channel[]|ArrayCollection
     */
    public function getChannels()
    {
        return $this->channels;
    }

    /**
     * @param Channel[]|ArrayCollection $channels
     *
     * @return Guild
     */
    public function setChannels(array $channels)
    {
        $this->channels = new ArrayCollection($channels);

        return $this;
    }

    /**
     * @param string $id
     *
     * @return bool|Channel
     */
    public function getChannelById($id)
    {
        foreach ($this->channels as $channel) {
            if ($channel->getId() === $id) {
                return $channel;
            }
        }

        return false;
    }
}
