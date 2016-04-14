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
use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\Permissions\RolePermission as Permission;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * A Guild is Discord's equivalent of a server. It contains all the Members, Channels, Roles, Bans etc.
 *
 * @property string       $id
 * @property string       $name
 * @property string       $icon
 * @property string       $region
 * @property string       $owner_id
 * @property array|Role[] $roles
 * @property \DateTime    $joined_at
 * @property string       $afk_channel_id
 * @property int          $afk_timeout
 * @property bool         $embed_enabled
 * @property string       $embed_channel_id
 * @property array        $features
 * @property string       $splash
 * @property array        $emojis
 * @property bool         $large
 * @property int          $verification_level
 * @property int          $member_count
 */
class Guild extends Part
{
    const REGION_DEFAULT    = self::REGION_US_WEST;

    const REGION_US_WEST    = 'us-west';

    const REGION_US_SOUTH   = 'us-south';

    const REGION_US_EAST    = 'us-east';

    const REGION_US_CENTRAL = 'us-central';

    const REGION_SINGAPORE  = 'singapore';

    const REGION_LONDON     = 'london';

    const REGION_SYDNEY     = 'sydney';

    const REGION_FRANKFURT  = 'frankfurt';

    const REGION_AMSTERDAM  = 'amsterdam';

    const LEVEL_OFF         = 0;

    const LEVEL_LOW         = 1;

    const LEVEL_MEDIUM      = 2;

    const LEVEL_TABLEFLIP   = 3;

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
    ];

    /**
     * {@inheritdoc}
     */
    protected $uris = [
        'get'    => 'guilds/:id',
        'create' => 'guilds',
        'update' => 'guilds/:id',
        'delete' => 'guilds/:id',
        'leave'  => 'users/@me/guilds/:id',
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
     * Leaves the guild.
     *
     * Does not leave the guild if you are the owner however, please use
     * delete() for that.
     *
     * @return bool Whether the attempt to leave succeeded or failed.
     *
     * @see \Discord\Parts\Part::delete() Used for leaving/deleting the guild if you are owner.
     */
    public function leave()
    {
        try {
            $request       = Guzzle::delete($this->replaceWithVariables($this->uris['leave']));
            $this->created = false;
            $this->deleted = true;
        } catch (\Exception $e) {
            throw new PartRequestFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * Transfers ownership of the guild to
     * another member.
     *
     * @param Member|int $member The member to transfer ownership to.
     *
     * @return bool Whether the attempt succeeded or failed.
     */
    public function transferOwnership($member)
    {
        if ($member instanceof Member) {
            $member = $member->id;
        }

        try {
            $request = Guzzle::patch(
                $this->replaceWithVariables('guilds/:id'),
                [
                    'owner_id' => $member,
                ]
            );

            if ($request->owner_id != $member) {
                return false;
            }

            $this->fill((array) $request);
        } catch (DiscordRequestFailedException $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns the guilds members.
     *
     * @return Collection A collection of members.
     */
    public function getMembersAttribute()
    {
        if ($members = Cache::get("guild.{$this->id}.members")) {
            return $members;
        }

        // Members aren't retrievable via REST anymore,
        // they will be set if the websocket is used.
        Cache::set("guild.{$this->id}.members", new Collection([], "guild.{$this->id}.members"));

        return Cache::get("guild.{$this->id}.members");
    }

    /**
     * Returns the guilds roles.
     *
     * @return Collection A collection of roles.
     */
    public function getRolesAttribute()
    {
        if (isset($this->attributes_cache['roles'])) {
            return $this->attributes_cache['roles'];
        }

        if ($roles = Cache::get("guild.{$this->id}.roles")) {
            return $roles;
        }

        $roles = [];

        foreach ($this->attributes['roles'] as $index => $role) {
            $perm                = new Permission();
            $perm->perms         = $role->permissions;
            $role                = (array) $role;
            $role['permissions'] = $perm;
            $role['guild_id']    = $this->id;
            $roles[$index]       = new Role($role, true);
        }

        $roles = new Collection($roles, "guild.{$this->id}.roles");

        Cache::set("guild.{$this->id}.roles", $roles);

        return $roles;
    }

    /**
     * Returns the owner.
     *
     * @return User An User part.
     */
    public function getOwnerAttribute()
    {
        if ($owner = Cache::get("user.{$this->owner_id}")) {
            return $owner;
        }

        $request = Guzzle::get($this->replaceWithVariables('users/:owner_id'));

        $owner = new User((array) $request, true);

        Cache::set("user.{$owner->id}", $owner);

        return $owner;
    }

    /**
     * Returns the guilds channels.
     *
     * @return Collection A collection of channels.
     */
    public function getChannelsAttribute()
    {
        if ($channels = Cache::get("guild.{$this->id}.channels")) {
            return $channels;
        }

        $channels = [];
        $request  = Guzzle::get($this->replaceWithVariables('guilds/:id/channels'));

        foreach ($request as $index => $channel) {
            $channel = new Channel((array) $channel, true);
            Cache::set("channel.{$channel->id}", $channel);
            $channels[$index] = $channel;
        }

        $channels = new Collection($channels, "guild.{$this->id}.channels");

        Cache::set("guild.{$this->id}.channels", $channels);

        return $channels;
    }

    /**
     * Returns the guilds bans.
     *
     * @return Collection A collection of bans.
     */
    public function getBansAttribute()
    {
        if ($bans = Cache::get("guild.{$this->id}.bans")) {
            return $bans;
        }

        $bans = [];

        try {
            $request = Guzzle::get($this->replaceWithVariables('guilds/:id/bans'));
        } catch (DiscordRequestFailedException $e) {
            return new Collection();
        }

        foreach ($request as $index => $ban) {
            $ban          = (array) $ban;
            $ban['guild'] = $this;
            $ban          = new Ban($ban, true);
            Cache::set("guild.{$this->id}.bans.{$ban->user_id}", $ban);
            $bans[$index] = $ban;
        }

        $bans = new Collection($bans, "guild.{$this->id}.bans");

        Cache::set("guild.{$this->id}.bans", $bans);

        return $bans;
    }

    /**
     * Returns the guilds invites.
     *
     * @return Collection A collection of invites.
     */
    public function getInvitesAttribute()
    {
        if (isset($this->attributes_cache['invites'])) {
            return $this->attributes_cache['invites'];
        }

        if ($invites = Cache::get("guild.{$this->id}.invites")) {
            return $invites;
        }

        $request = Guzzle::get($this->replaceWithVariables('guilds/:id/invites'));
        $invites = [];

        foreach ($request as $index => $invite) {
            $invite = new Invite((array) $invite, true);
            Cache::set("invite.{$invite->id}", $invite);
            $invites[$index] = $invite;
        }

        $invites = new Collection($invites, "guild.{$this->id}.invites");

        Cache::set("guild.{$this->id}.invites", $invites);

        return $invites;
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

        return "https://discordapp.com/{$this->attributes['id']}/icons/{$this->attributes['icon']}.jpg";
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

        return "https://discordapp.com/api/guilds/{$this->id}/splashes/{$this->attributes['splash']}.jpg";
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
        Cache::set("guild.{$this->id}.{$key}", $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes()
    {
        return [
            'name'   => $this->name,
            'region' => $this->validateRegion(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        return [
            'name'               => $this->name,
            'region'             => $this->region,
            'logo'               => $this->logo,
            'splash'             => $this->splash,
            'verification_level' => $this->verification_level,
            'afk_channel_id'     => $this->afk_channel_id,
            'afk_timeout'        => $this->afk_timeout,
        ];
    }
}
