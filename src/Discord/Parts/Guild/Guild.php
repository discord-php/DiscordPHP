<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Carbon\Carbon;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\Guild\BanRepository;
use Discord\Repository\Guild\ChannelRepository;
use Discord\Repository\Guild\EmojiRepository;
use Discord\Repository\Guild\InviteRepository;
use Discord\Repository\Guild\MemberRepository;
use Discord\Repository\Guild\RoleRepository;
use Illuminate\Support\Collection;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function React\Partial\bind as Bind;

/**
 * A Guild is Discord's equivalent of a server. It contains all the Members, Channels, Roles, Bans etc.
 *
 * @property string            $id                 The unique identifier of the guild.
 * @property string            $name               The name of the guild.
 * @property string            $icon               The URL to the guild icon.
 * @property string            $icon_hash          The icon hash for the guild.
 * @property string            $region             The region the guild's voice channels are hosted in.
 * @property User              $owner              The owner of the guild.
 * @property string            $owner_id           The unique identifier of the owner of the guild.
 * @property Carbon            $joined_at          A timestamp of when the current user joined the guild.
 * @property string            $afk_channel_id     The unique identifier of the AFK channel ID.
 * @property int               $afk_timeout        How long you will remain in the voice channel until you are moved into the AFK channel.
 * @property bool              $embed_enabled      Whether the embed is enabled.
 * @property string            $embed_channel_id   The unique identifier of the channel that will be used for the embed.
 * @property string[]          $features           An array of features that the guild has.
 * @property string            $splash             The URL to the guild splash.
 * @property string            $discovery_splash Discovery splash hash. Only for discoverable guilds.
 * @property string            $splash_hash        The splash hash for the guild.
 * @property bool              $large              Whether the guild is considered 'large' (over 250 members).
 * @property int               $verification_level The verification level used for the guild.
 * @property int               $member_count       How many members are in the guild.
 * @property int               $default_message_notifications Default notification level.
 * @property int               $explicit_content_filter Explicit content filter level.
 * @property int               $mfa_level MFA level required to join.
 * @property string            $application_id Application that made the guild, if made by one.
 * @property bool              $widget_enabled Is server widget enabled.
 * @property string            $widget_channel_id Channel that the widget will create an invite to.
 * @property string            $system_channel_id Channel that system notifications are posted in.
 * @property int               $system_channel_flags Flags for the system channel.
 * @property string            $rules_channel_id Channel that the rules are in.
 * @property object[]          $voice_states Array of voice states.
 * @property int               $max_presences Maximum amount of presences allowed in the guild.
 * @property int               $max_members Maximum amount of members allowed in the guild.
 * @property string            $vanity_url_code Vanity URL code for the guild.
 * @property string            $description Guild description if it is discoverable.
 * @property string            $banner Banner hash.
 * @property int               $premium_tier Server boost level.
 * @property int               $premium_subscription_count Number of boosts in the guild.
 * @property string            $preferred_locale Preferred locale of the guild.
 * @property string            $public_updates_channel_id Notice channel id.
 * @property int               $max_video_channel_users Maximum amount of users allowed in a video channel.
 * @property int               $approximate_member_count
 * @property int               $approximate_presence_count
 * @property RoleRepository    $roles
 * @property ChannelRepository $channels
 * @property MemberRepository  $members
 * @property InviteRepository  $invites
 * @property BanRepository     $bans
 * @property EmojiRepository   $emojis
 */
class Guild extends Part
{
    const REGION_DEFAULT = 'us_west';

    const LEVEL_OFF = 0;
    const LEVEL_LOW = 1;
    const LEVEL_MEDIUM = 2;
    const LEVEL_TABLEFLIP = 3;
    const LEVEL_DOUBLE_TABLEFLIP = 4;

    const SUPPRESS_JOIN_NOTIFICATIONS = (1 << 0);
    const SUPPRESS_PREMIUM_SUBSCRIPTION = (1 << 1);

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
        'discovery_splash',
        'emojis',
        'large',
        'verification_level',
        'member_count',
        'default_message_notifications',
        'explicit_content_filter',
        'mfa_level',
        'application_id',
        'widget_enabled',
        'widget_channel_id',
        'system_channel_id',
        'system_channel_flags',
        'rules_channel_id',
        'voice_states',
        'max_presences',
        'max_members',
        'vanity_url_code',
        'description',
        'banner',
        'premium_tier',
        'premium_subscription_count',
        'preferred_locale',
        'public_updates_channel_id',
        'max_video_channel_users',
        'approximate_member_count',
        'approximate_presence_count',
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
        'emojis' => EmojiRepository::class,
    ];

    /**
     * An array of valid regions.
     *
     * @var Collection|null
     */
    protected $regions;

    /**
     * Returns the channels invites.
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function getInvites(): PromiseInterface
    {
        $deferred = new Deferred();

        $this->http->get($this->replaceWithVariables('guilds/:id/invites'))->then(
            function ($response) use ($deferred) {
                $invites = new Collection();

                foreach ($response as $invite) {
                    $invite = $this->factory->create(Invite::class, (array) $invite, true);
                    $invites->push($invite);
                }

                $deferred->resolve($invites);
            },
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Returns the owner.
     *
     * @return PromiseInterface
     */
    protected function getOwnerAttribute()
    {
        return $this->discord->users->get('id', $this->owner_id);
    }

    /**
     * Returns the joined_at attribute.
     *
     * @return Carbon|null The joined_at attribute.
     * @throws \Exception
     */
    protected function getJoinedAtAttribute()
    {
        if (! array_key_exists('joined_at', $this->attributes)) {
            return null;
        }

        return new Carbon($this->attributes['joined_at']);
    }

    /**
     * Returns the guilds icon.
     *
     * @param string $format The image format.
     * @param int    $size   The size of the image.
     *
     * @return string|null The URL to the guild icon or null.
     */
    public function getIconAttribute(string $format = 'jpg', int $size = 1024)
    {
        if (is_null($this->attributes['icon'])) {
            return null;
        }

        if (false === array_search($format, ['png', 'jpg', 'webp'])) {
            $format = 'jpg';
        }

        return "https://cdn.discordapp.com/icons/{$this->id}/{$this->attributes['icon']}.{$format}?size={$size}";
    }

    /**
     * Returns the guild icon hash.
     *
     * @return string|null The guild icon hash or null.
     */
    protected function getIconHashAttribute()
    {
        return $this->attributes['icon'];
    }

    /**
     * Returns the guild splash.
     *
     * @param string $format The image format.
     * @param int    $size   The size of the image.
     *
     * @return string|null The URL to the guild splash or null.
     */
    public function getSplashAttribute(string $format = 'jpg', int $size = 2048)
    {
        if (is_null($this->attributes['splash'])) {
            return null;
        }

        if (false === array_search($format, ['png', 'jpg', 'webp'])) {
            $format = 'jpg';
        }

        return "https://cdn.discordapp.com/slashes/{$this->id}/{$this->attributes['splash']}.{$format}?size={$size}";
    }

    /**
     * Returns the guild splash hash.
     *
     * @return string|null The guild splash hash or null.
     */
    protected function getSplashHashAttribute()
    {
        return $this->attributes['splash'];
    }

    /**
     * Gets the voice regions available.
     *
     * @return PromiseInterface
     */
    public function getVoiceRegions(): PromiseInterface
    {
        $deferred = new Deferred();

        $this->http->get('voice/regions')->then(function ($regions) use ($deferred) {
            $regions = new Collection($regions);

            $this->regions = $regions;
            $deferred->resolve($regions);
        }, Bind([$deferred, 'reject']));

        return $deferred->promise();
    }

    /**
     * Creates a role.
     *
     * @param array $data The data to fill the role with.
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function createRole(array $data = []): PromiseInterface
    {
        $deferred = new Deferred();

        $rolePart = $this->factory->create(Role::class);

        $this->roles->save($rolePart)->then(
            function ($role) use ($deferred, $data) {
                $role->fill((array) $data);

                $this->roles->save($role)->then(
                    function ($role) use ($deferred) {
                        $deferred->resolve($role);
                    },
                    Bind([$deferred, 'reject'])
                );
            },
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Transfers ownership of the guild to
     * another member.
     *
     * @param Member|int $member The member to transfer ownership to.
     *
     * @return PromiseInterface
     */
    public function transferOwnership($member): PromiseInterface
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
        )->then(
            function ($response) use ($member, $deferred) {
                if ($response->owner_id != $member) {
                    $deferred->reject(new \Exception('Ownership was not transferred correctly.'));
                    $this->fill((array) $response);
                } else {
                    $deferred->resolve();
                }
            },
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Validates the specified region.
     *
     * @return PromiseInterface
     *
     * @see self::REGION_DEFAULT The default region.
     */
    public function validateRegion(): PromiseInterface
    {
        $deferred = new Deferred();

        $validate = function () use ($deferred) {
            $regions = $this->regions->map(function ($region) {
                return $region->id;
            })->toArray();

            if (! in_array($this->region, $regions)) {
                $deferred->resolve(self::REGION_DEFAULT);
            } else {
                $deferred->resolve($this->region);
            }
        };

        if (! is_null($this->regions)) {
            $validate();
        } else {
            $this->getVoiceRegions()->then($validate, Bind([$deferred, 'reject']));
        }

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'region' => $this->region,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes(): array
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

    /**
     * {@inheritdoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_id' => $this->id,
        ];
    }
}
