<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Discord\Cache\Cache;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\Guild\BanRepository;
use Discord\Repository\Guild\ChannelRepository;
use Discord\Repository\Guild\InviteRepository;
use Discord\Repository\Guild\MemberRepository;
use Discord\Repository\Guild\RoleRepository;
use React\Promise\Deferred;

/**
 * A Guild is Discord's equivalent of a server. It contains all the Members, Channels, Roles, Bans etc.
 *
 * @property string                     $id
 * @property string                     $name
 * @property string                     $icon
 * @property string                     $region
 * @property string                     $owner_id
 * @property array|Role[]               $roles
 * @property \DateTime                  $joined_at
 * @property string                     $afk_channel_id
 * @property int                        $afk_timeout
 * @property bool                       $embed_enabled
 * @property string                     $embed_channel_id
 * @property array                      $features
 * @property string                     $splash
 * @property array                      $emojis
 * @property bool                       $large
 * @property int                        $verification_level
 * @property int                        $member_count
 * @property Collection|array|Channel[] $channels
 * @property Collection|array|Member[]  $members
 */
class Guild extends Part
{
    const REGION_DEFAULT = self::REGION_US_WEST;
    const REGION_US_WEST = 'us-west';
    const REGION_US_SOUTH = 'us-south';
    const REGION_US_EAST = 'us-east';
    const REGION_US_CENTRAL = 'us-central';
    const REGION_SINGAPORE = 'singapore';
    const REGION_LONDON = 'london';
    const REGION_SYDNEY = 'sydney';
    const REGION_FRANKFURT = 'frankfurt';
    const REGION_AMSTERDAM = 'amsterdam';

    const LEVEL_OFF = 0;
    const LEVEL_LOW = 1;
    const LEVEL_MEDIUM = 2;
    const LEVEL_TABLEFLIP = 3;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'name',
        'icon',
        'region',
        'owner_id',
        'roles',
        'joined_at',
        'afk_channel_id',
        'afk_timeout',
        'embed_enabled',
        'embed_channel_id',
        'features',
        'splash',
        'emojis',
        'large',
        'verification_level',
        'member_count',
        'default_message_notifications',
    ];

    /**
     * {@inheritdoc}
     */
    protected $repositories = [
        'members' => MemberRepository::class,
        'roles' => RoleRepository::class,
        'channels' => ChannelRepository::class,
        'bans' => BanRepository::class,
        'invites' => InviteRepository::class,
    ];

    /**
     * An array of valid regions.
     *
     * @var array Array of valid regions.
     */
    protected $regions = [
        self::REGION_US_WEST,
        self::REGION_US_SOUTH,
        self::REGION_US_EAST,
        self::REGION_US_CENTRAL,
        self::REGION_LONDON,
        self::REGION_SINGAPORE,
        self::REGION_SYDNEY,
        self::REGION_FRANKFURT,
        self::REGION_AMSTERDAM,
    ];

    /**
     * Transfers ownership of the guild to
     * another member.
     *
     * @param Member|int $member The member to transfer ownership to.
     *
     * @return \React\Promise\Promise
     */
    public function transferOwnership($member)
    {
        $deferred = new Deferred();

        if ($member instanceof Member) {
            $member = $member->id;
        }

        $this->http->patch(
            $this->replaceWithVariables('guilds/:id'),
            [
                'owner_id' => $member,
            ]
        )->then(function ($response) use ($member, $deferred) {
            if ($respose->owner_id != $member) {
                $deferred->reject(new \Exception('Ownership was not transferred correctly.'));
                $this->fill((array) $response);
            } else {
                $deferred->resolve();
            }
        }, \React\Partial\bind_right($this->reject, $deferred));

        return $deferred->promise();
    }

    /**
     * Returns the owner.
     *
     * @return \React\Promise\Promise
     */
    public function getOwnerAttribute()
    {
        $deferred = new Deferred();

        if ($owner = Cache::get("user.{$this->owner_id}")) {
            $deferred->resolve($owner);

            return $deferred->promise();
        }

        $this->http->get($this->replaceWithVariables('users/:owner_id'))->then(function ($response) use ($deferred) {
            $owner = $this->factory->create(User::class, $response, true);
            $this->cache->set("user.{$owner->id}", $owner);
            $deferred->resolve($owner);
        }, \React\Partial\bind_right($this->reject, $deferred));

        return $deferred->promise();
    }

    /**
     * Returns the guilds icon.
     *
     * @return string|null The URL to the guild icon or null.
     */
    public function getIconAttribute()
    {
        if (is_null($this->attributes['icon'])) {
            return;
        }

        return "https://cdn.discordapp.com/icons/{$this->attributes['id']}/{$this->attributes['icon']}.jpg";
    }

    /**
     * Returns the guild icon hash.
     *
     * @return string|null The guild icon hash or null.
     */
    public function getIconHashAttribute()
    {
        return $this->attributes['icon'];
    }

    /**
     * Returns the guild splash.
     *
     * @return string|null The URL to the guild splash or null.
     */
    public function getSplashAttribute()
    {
        if (is_null($this->attributes['splash'])) {
            return;
        }

        return "https://cdn.discordapp.com/splash/{$this->attributes['id']}/{$this->attributes['icon']}.jpg";
    }

    /**
     * Returns the guild splash hash.
     *
     * @return string|null The guild splash hash or null.
     */
    public function getSplashHashAttribute()
    {
        return $this->attributes['splash'];
    }

    /**
     * Validates the specified region.
     *
     * @return string Returns the region if it is valid or default.
     *
     * @see self::REGION_DEFAULT The default region.
     */
    public function validateRegion()
    {
        if (! in_array($this->region, $this->regions)) {
            return self::REGION_DEFUALT;
        }

        return $this->region;
    }

    /**
     * {@inheritdoc}
     */
    public function setCache($key, $value)
    {
        $this->cache->set("guild.{$this->id}.{$key}", $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes()
    {
        return [
            'name' => $this->name,
            'region' => $this->validateRegion(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        return [
            'name' => $this->name,
            'region' => $this->region,
            'logo' => $this->logo,
            'splash' => $this->splash,
            'verification_level' => $this->verification_level,
            'afk_channel_id' => $this->afk_channel_id,
            'afk_timeout' => $this->afk_timeout,
        ];
    }
}
