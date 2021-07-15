<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\Guild\BanRepository;
use Discord\Repository\Guild\ChannelRepository;
use Discord\Repository\Guild\EmojiRepository;
use Discord\Repository\Guild\InviteRepository;
use Discord\Repository\Guild\MemberRepository;
use Discord\Repository\Guild\RoleRepository;
use Discord\Parts\Guild\AuditLog\AuditLog;
use Discord\Parts\Guild\AuditLog\Entry;
use Exception;
use React\Promise\ExtendedPromiseInterface;
use ReflectionClass;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A Guild is Discord's equivalent of a server. It contains all the Members, Channels, Roles, Bans etc.
 *
 * @property string            $id                            The unique identifier of the guild.
 * @property string            $name                          The name of the guild.
 * @property string            $icon                          The URL to the guild icon.
 * @property string            $icon_hash                     The icon hash for the guild.
 * @property string            $region                        The region the guild's voice channels are hosted in.
 * @property User              $owner                         The owner of the guild.
 * @property string            $owner_id                      The unique identifier of the owner of the guild.
 * @property Carbon            $joined_at                     A timestamp of when the current user joined the guild.
 * @property string            $afk_channel_id                The unique identifier of the AFK channel ID.
 * @property int               $afk_timeout                   How long you will remain in the voice channel until you are moved into the AFK channel.
 * @property string[]          $features                      An array of features that the guild has.
 * @property string            $splash                        The URL to the guild splash.
 * @property string            $discovery_splash              Discovery splash hash. Only for discoverable guilds.
 * @property string            $splash_hash                   The splash hash for the guild.
 * @property bool              $large                         Whether the guild is considered 'large' (over 250 members).
 * @property int               $verification_level            The verification level used for the guild.
 * @property int               $member_count                  How many members are in the guild.
 * @property int               $default_message_notifications Default notification level.
 * @property int               $explicit_content_filter       Explicit content filter level.
 * @property int               $mfa_level                     MFA level required to join.
 * @property string            $application_id                Application that made the guild, if made by one.
 * @property bool              $widget_enabled                Is server widget enabled.
 * @property string            $widget_channel_id             Channel that the widget will create an invite to.
 * @property string            $system_channel_id             Channel that system notifications are posted in.
 * @property int               $system_channel_flags          Flags for the system channel.
 * @property string            $rules_channel_id              Channel that the rules are in.
 * @property object[]          $voice_states                  Array of voice states.
 * @property int               $max_presences                 Maximum amount of presences allowed in the guild.
 * @property int               $max_members                   Maximum amount of members allowed in the guild.
 * @property string            $vanity_url_code               Vanity URL code for the guild.
 * @property string            $description                   Guild description if it is discoverable.
 * @property string            $banner                        Banner hash.
 * @property int               $premium_tier                  Server boost level.
 * @property int               $premium_subscription_count    Number of boosts in the guild.
 * @property string            $preferred_locale              Preferred locale of the guild.
 * @property string            $public_updates_channel_id     Notice channel id.
 * @property int               $max_video_channel_users       Maximum amount of users allowed in a video channel.
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
    public const REGION_DEFAULT = 'us_west';

    public const LEVEL_OFF = 0;
    public const LEVEL_LOW = 1;
    public const LEVEL_MEDIUM = 2;
    public const LEVEL_TABLEFLIP = 3;
    public const LEVEL_DOUBLE_TABLEFLIP = 4;

    public const SUPPRESS_JOIN_NOTIFICATIONS = (1 << 0);
    public const SUPPRESS_PREMIUM_SUBSCRIPTION = (1 << 1);

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function getInvites(): ExtendedPromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::GUILD_INVITES, $this->id))->then(function ($response) {
            $invites = new Collection();

            foreach ($response as $invite) {
                $invite = $this->factory->create(Invite::class, $invite, true);
                $invites->push($invite);
            }

            return $invites;
        });
    }

    /**
     * Unbans a member. Alias for `$guild->bans->unban($user)`.
     *
     * @param User|string $user
     *
     * @return ExtendedPromiseInterface
     */
    public function unban($user): ExtendedPromiseInterface
    {
        return $this->bans->unban($user);
    }

    /**
     * Returns the owner.
     *
     * @return ExtendedPromiseInterface
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
     * @return ExtendedPromiseInterface
     */
    public function getVoiceRegions(): ExtendedPromiseInterface
    {
        if (! is_null($this->regions)) {
            return \React\Promise\resolve($this->regions);
        }

        return $this->http->get('voice/regions')->then(function ($regions) {
            $regions = new Collection($regions);

            $this->regions = $regions;

            return $regions;
        });
    }

    /**
     * Creates a role.
     *
     * @param array $data The data to fill the role with.
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function createRole(array $data = []): ExtendedPromiseInterface
    {
        $rolePart = $this->factory->create(Role::class);

        return $this->roles->save($rolePart)->then(function ($role) use ($data) {
            $role->fill((array) $data);

            return $this->roles->save($role);
        });
    }

    /**
     * Leaves the guild.
     *
     * @return ExtendedPromiseInterface
     */
    public function leave(): ExtendedPromiseInterface
    {
        return $this->discord->guilds->leave($this->id);
    }

    /**
     * Transfers ownership of the guild to
     * another member.
     *
     * @param Member|int $member The member to transfer ownership to.
     *
     * @return ExtendedPromiseInterface
     */
    public function transferOwnership($member): ExtendedPromiseInterface
    {
        if ($member instanceof Member) {
            $member = $member->id;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD), ['owner_id' => $member])->then(function ($response) use ($member) {
            if ($response->owner_id != $member) {
                throw new Exception('Ownership was not transferred correctly.');
            }

            return $this;
        });
    }

    /**
     * Validates the specified region.
     *
     * @return ExtendedPromiseInterface
     *
     * @see self::REGION_DEFAULT The default region.
     */
    public function validateRegion(): ExtendedPromiseInterface
    {
        return $this->getVoiceRegions()->then(function () {
            $regions = $this->regions->map(function ($region) {
                return $region->id;
            })->toArray();

            if (! in_array($this->region, $regions)) {
                return self::REGION_DEFAULT;
            }

            return $this->region;
        });
    }

    /**
     * Returns an audit log object for the query.
     *
     * @param array $options An array of options.
     *                       user_id => filter the log for actions made by a user
     *                       action_type => the type of audit log event
     *                       before => filter the log before a certain entry id
     *                       limit => how many entries are returned (default 50, minimum 1, maximum 100)
     *
     * @return ExtendedPromiseInterface
     */
    public function getAuditLog(array $options = []): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined([
            'user_id',
            'action_type',
            'before',
            'limit',
        ])
        ->setAllowedTypes('user_id', ['string', 'int', Member::class, User::class])
        ->setAllowedTypes('action_type', 'int')
        ->setAllowedTypes('before', ['string', 'int', Entry::class])
        ->setAllowedTypes('limit', 'int')
        ->setAllowedValues('action_type', array_values((new ReflectionClass(Entry::class))->getConstants()))
        ->setAllowedValues('limit', range(1, 100));

        $options = $resolver->resolve($options);

        if ($options['user_id'] ?? null instanceof Part) {
            $options['user_id'] = $options['user_id']->id;
        }

        if ($options['before'] ?? null instanceof Part) {
            $options['before'] = $options['before']->id;
        }

        $endpoint = Endpoint::bind(Endpoint::AUDIT_LOG);

        foreach ($options as $key => $value) {
            $endpoint->addQuery($key, $value);
        }

        return $this->http->get($endpoint)->then(function ($response) {
            $response = (array) $response;
            $response['guild_id'] = $this->id;

            return $this->factory->create(AuditLog::class, $response, true);
        });
    }

    /**
     * Updates the positions of a list of given roles.
     *
     * The `$roles` array should be an associative array where the LHS key is the position,
     * and the RHS value is a `Role` object or a string ID, e.g. [1 => 'role_id_1', 3 => 'role_id_3'].
     *
     * @param array $roles
     *
     * @return ExtendedPromiseInterface
     */
    public function updateRolePositions(array $roles): ExtendedPromiseInterface
    {
        $payload = [];

        foreach ($roles as $position => $role) {
            $payload[] = [
                'id' => ($role instanceof Role) ? $role->id : $role,
                'position' => $position,
            ];
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_ROLES, $this->id), $payload)
            ->then(function () {
                return $this;
            });
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'region' => $this->region,
            'icon' => $this->attributes['icon'],
            'verification_level' => $this->verification_level,
            'default_message_notifications' => $this->default_message_notifications,
            'explicit_content_filter' => $this->explicit_content_filter,
            'afk_channel_id' => $this->afk_channel_id,
            'afk_timeout' => $this->afk_timeout,
            'system_channel_id' => $this->system_channel_id,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'region' => $this->region,
            'verification_level' => $this->verification_level,
            'default_message_notifications' => $this->default_message_notifications,
            'explicit_content_filter' => $this->explicit_content_filter,
            'afk_channel_id' => $this->afk_channel_id,
            'afk_timeout' => $this->afk_timeout,
            'icon' => $this->attributes['icon'],
            'splash' => $this->attributes['splash'],
            'banner' => $this->attributes['banner'],
            'system_channel_id' => $this->system_channel_id,
            'rules_channel_id' => $this->rules_channel_id,
            'public_updates_channel_id' => $this->public_updates_channel_id,
            'preferred_locale' => $this->preferred_locale,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_id' => $this->id,
        ];
    }

    /**
     * Returns the timestamp of when the guild was created.
     *
     * @return float
     */
    public function createdTimestamp()
    {
        return \Discord\getSnowflakeTimestamp($this->id);
    }
}
