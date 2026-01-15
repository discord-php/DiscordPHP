<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\OAuth;

use Discord\Helpers\ExCollectionInterface;
use Discord\Http\Endpoint;
use Discord\Parts\Part;
use Discord\Parts\Permissions\Permission;
use Discord\Parts\User\User;
use Discord\Repository\ActivityInstanceRepository;
use Discord\Repository\Monetization\EntitlementRepository;
use Discord\Repository\Monetization\SKURepository;
use Discord\Repository\Interaction\GlobalCommandRepository;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * The OAuth2 application of the bot.
 *
 * @link https://discord.com/developers/docs/resources/application
 *
 * @since 7.0.0
 *
 * @property string             $id                                   The client ID of the OAuth application.
 * @property string             $name                                 The name of the OAuth application.
 * @property string|null        $icon                                 The icon URL of the OAuth application.
 * @property string             $icon_hash                            The icon hash of the OAuth application.
 * @property string             $description                          The description of the OAuth application.
 * @property string[]           $rpc_origins                          An array of RPC origin URLs.
 * @property bool               $bot_public                           When false only app owner can join the app's bot to guilds.
 * @property bool               $bot_require_code_grant               When true the app's bot will only join upon completion of the full oauth2 code grant flow.
 * @property User|null          $bot                                  The partial user object for the bot user associated with the application.
 * @property string|null        $terms_of_service_url                 The url of the app's terms of service.
 * @property string|null        $privacy_policy_url                   The url of the app's privacy policy
 * @property User|null          $owner                                The owner of the OAuth application.
 * @property string             $verify_key                           The hex encoded key for verification in interactions and the GameSDK's GetTicket.
 * @property Team|null          $team                                 If the application belongs to a team, this will be a list of the members of that team.
 * @property string|null        $guild_id                             If this application is a game sold on Discord, this field will be the guild to which it has been linked.
 * @property string|null        $primary_sku_id                       If this application is a game sold on Discord, this field will be the id of the "Game SKU" that is created, if exists.
 * @property string|null        $slug                                 If this application is a game sold on Discord, this field will be the URL slug that links to the store page.
 * @property string|null        $cover_image                          The application's default rich presence invite cover image URL.
 * @property string|null        $cover_image_hash                     The application's default rich presence invite cover image hash.
 * @property int                $flags                                The application's public flags.
 * @property int|null           $approximate_guild_count              The application's approximate count of the app's guild membership.
 * @property int|null           $approximate_user_install_count       The approximate count of users that have installed the app.
 * @property int|null           $approximate_user_authorization_count The approximate count of users that have OAuth2 authorizations for the app.
 * @property string[]|null      $redirect_uris                        Array of redirect URIs for the application.
 * @property string|null        $interactions_endpoint_url            The interactions endpoint URL for the application.
 * @property string|null        $role_connections_verification_url    The application's role connection verification entry point, which when configured will render the app as a verification method in the guild role verification configuration.
 * @property string|null        $event_webhooks_url                   Event webhooks URL for the app to receive webhook events.
 * @property int                $event_webhooks_status                If webhook events are enabled for the app. `1` (default) means disabled, `2` means enabled, and `3` means disabled by Discord.
 * @property string[]|null      $event_webhooks_types                 List of Webhook event types the app subscribes to.
 * @property string[]|null      $tags                                 Up to 5 tags describing the content and functionality of the application.
 * @property InstallParams|null $install_params                       Settings for the application's default in-app authorization link, if enabled.
 * @property object[]|null      $integration_types_config             Default scopes and permissions for each supported installation context. Value for each key is an integration type configuration object (0 for GUILD_INSTALL, 1 for USER_INSTALL).
 * @property string|null        $custom_install_url                   The application's default custom authorization link, if enabled.
 *
 * @property string $invite_url The invite URL to invite the bot to a guild.
 *
 * @property GlobalCommandRepository    $commands           The application global commands.
 * @property EntitlementRepository      $entitlements       The application entitlements.
 * @property SKURepository              $skus               The application SKUs.
 * @property ActivityInstanceRepository $activity_instances The application activity instances.
 */
class Application extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'name',
        'icon',
        'description',
        'rpc_origins',
        'bot_public',
        'bot_require_code_grant',
        'bot',
        'terms_of_service_url',
        'privacy_policy_url',
        'owner',
        'verify_key',
        'team',
        'guild_id',
        'guild',
        'primary_sku_id',
        'slug',
        'cover_image',
        'flags',
        'approximate_guild_count',
        'approximate_user_install_count',
        'approximate_user_authorization_count',
        'redirect_uris',
        'interactions_endpoint_url',
        'role_connections_verification_url',
        'event_webhooks_url',
        'event_webhooks_status',
        'event_webhooks_types',
        'tags',
        'install_params',
        'integration_types_config',
        'custom_install_url',
    ];

    /** Indicates if an app uses the Auto Moderation API. */
    public const APPLICATION_AUTO_MODERATION_RULE_CREATE_BADGE = (1 << 6);
    /** Intent required for bots in 100 or more servers to receive `presence_update` events. */
    public const GATEWAY_PRESENCE = (1 << 12);
    /** Intent required for bots in under 100 servers to receive `presence_update` events, found on the Bot page in your app's settings. */
    public const GATEWAY_PRESENCE_LIMITED = (1 << 13);
    /** Intent required for bots in 100 or more servers to receive member-related events like `guild_member_add`. */
    public const GATEWAY_GUILD_MEMBERS = (1 << 14);
    /** Intent required for bots in under 100 servers to receive member-related events like `guild_member_add`, found on the Bot page in your app's settings. */
    public const GATEWAY_GUILD_MEMBERS_LIMITED = (1 << 15);
    /** Indicates unusual growth of an app that prevents verification. */
    public const VERIFICATION_PENDING_GUILD_LIMIT = (1 << 16);
    /** Indicates if an app is embedded within the Discord client (currently unavailable publicly). */
    public const EMBEDDED = (1 << 17);
    /** Intent required for bots in 100 or more servers to receive message content. */
    public const GATEWAY_MESSAGE_CONTENT = (1 << 18);
    /** 	Intent required for bots in under 100 servers to receive message content, found on the Bot page in your app's settings. */
    public const GATEWAY_MESSAGE_CONTENT_LIMITED = (1 << 19);
    /** Indicates if an app has registered global application commands. */
    public const APPLICATION_COMMAND_BADGE = (1 << 23);
    /** @todo Undocumented. */
    public const ACTIVE = (1 << 24);

    /**	App is installable to servers. */
    public const INTEGRATION_TYPE_GUILD_INSTALL = 0;
    /** App is installable to users. */
    public const INTEGRATION_TYPE_USER_INSTALL = 1;

    /**
     * @inheritDoc
     */
    protected $repositories = [
        'commands' => GlobalCommandRepository::class,
        'entitlements' => EntitlementRepository::class,
        'skus' => SKURepository::class,
        'activity_instances' => ActivityInstanceRepository::class,
    ];

    /**
     * Returns a list of application role connection metadata objects for the given application.
     *
     * @link https://discord.com/developers/docs/resources/application-role-connection-metadata#get-application-role-connection-metadata-records
     *
     * @since 10.29.0
     *
     * @return PromiseInterface<ExCollectionInterface<ApplicationRoleConnectionMetadata>|ApplicationRoleConnectionMetadata[]> A collection of application role connection metadata objects.
     */
    public function getApplicationRoleConnectionMetadataRecords(): PromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::APPLICATION_ROLE_CONNECTION_METADATA, $this->id))
            ->then(function ($response) {
                /** @var ExCollectionInterface<ApplicationRoleConnectionMetadata> $collection */
                $collection = $this->discord->getCollectionClass()::for(ApplicationRoleConnectionMetadata::class);

                foreach ($response as $record) {
                    $collection[] = $this->factory->part(ApplicationRoleConnectionMetadata::class, (array) $record, true);
                }

                return $collection;
            });
    }

    /**
     * Updates and returns a list of application role connection metadata objects for the given application.
     *
     * @link https://discord.com/developers/docs/resources/application-role-connection-metadata#get-application-role-connection-metadata-records
     *
     * @since 10.29.0
     *
     * @param ApplicationRoleConnectionMetadata[] $data The new metadata records.
     *
     * @return PromiseInterface<ExCollectionInterface<ApplicationRoleConnectionMetadata>|ApplicationRoleConnectionMetadata[]> A collection of application role connection metadata objects.
     */
    public function updateApplicationRoleConnectionMetadataRecords($data = []): PromiseInterface
    {
        if (count($data) > 5) {
            return reject(new \InvalidArgumentException('You can only have up to 5 application role connection metadata records.'));
        }

        return $this->http->put(Endpoint::bind(Endpoint::APPLICATION_ROLE_CONNECTION_METADATA, $this->id), $data)
            ->then(function ($response) {
                /** @var ExCollectionInterface<ApplicationRoleConnectionMetadata> $collection */
                $collection = $this->discord->getCollectionClass()::for(ApplicationRoleConnectionMetadata::class);

                foreach ($response as $record) {
                    $collection[] = $this->factory->part(ApplicationRoleConnectionMetadata::class, (array) $record, true);
                }

                return $collection;
            });
    }

    /**
     * Returns a serialized activity instance, if it exists.
     * Useful for preventing unwanted activity sessions.
     *
     * @param ActivityInstance|string $instance_id The activity instance ID.
     *
     * @throws \DomainException Missing instance ID.
     *
     * @return PromiseInterface<?ActivityInstance>
     */
    public function getActivityInstance($instance_id): PromiseInterface
    {
        if (! isset($instance_id)) {
            return reject(new \DomainException('You must provide an instance ID to get an activity instance.'));
        }

        if ($instance_id instanceof ActivityInstance) {
            $instance_id = $instance_id->id;
        }

        return $this->http->get(Endpoint::bind(Endpoint::APPLICATION_ACTIVITY_INSTANCE, $this->id, $instance_id))
            ->then(function ($response) {
                if (empty($response)) {
                    return null;
                }

                return $this->factory->part(ActivityInstance::class, (array) $response, true);
            });
    }

    /**
     * Returns the application icon.
     *
     * @param string $format The image format.
     * @param int    $size   The size of the image.
     *
     * @return string|null The URL to the application icon or null.
     */
    public function getIconAttribute(string $format = 'webp', int $size = 1024): ?string
    {
        if (! isset($this->attributes['icon'])) {
            return null;
        }

        static $allowed = ['png', 'jpg', 'webp'];
        if (! in_array(strtolower($format), $allowed)) {
            $format = 'webp';
        }

        return "https://cdn.discordapp.com/app-icons/{$this->id}/{$this->attributes['icon']}.{$format}?size={$size}";
    }

    /**
     * Returns the application icon attribute.
     *
     * @return string|null The application icon hash or null.
     */
    protected function getIconHashAttribute(): ?string
    {
        return $this->attributes['icon'];
    }

    /**
     * Returns the bot user of the application.
     *
     * @return User|null The partial user object for the bot user associated with the application.
     */
    protected function getBotAttribute(): ?User
    {
        if (empty($this->attributes['bot'])) {
            return null;
        }

        if ($bot = $this->discord->users->get('id', $this->attributes['bot']->id)) {
            return $bot;
        }

        return $this->attributePartHelper('bot', User::class);
    }

    /**
     * Returns the owner of the application.
     *
     * @return User|null Owner of the application.
     */
    protected function getOwnerAttribute(): ?User
    {
        if (empty($this->attributes['owner'])) {
            return null;
        }

        if ($owner = $this->discord->users->get('id', $this->attributes['owner']->id)) {
            return $owner;
        }

        return $this->attributePartHelper('owner', User::class);
    }

    /**
     * Returns the team of the application.
     *
     * @return Team|null If the application belongs to a team, this will be a list of the members of that team.
     */
    protected function getTeamAttribute(): ?Team
    {
        return $this->attributePartHelper('team', Team::class);
    }

    /**
     * Returns the application cover image.
     *
     * @param string $format The image format.
     * @param int    $size   The size of the image.
     *
     * @return string|null The URL to the application cover image or null.
     */
    public function getCoverImageAttribute(string $format = 'webp', int $size = 1024): ?string
    {
        if (! isset($this->attributes['cover_image'])) {
            return null;
        }

        static $allowed = ['png', 'jpg', 'webp'];
        if (! in_array(strtolower($format), $allowed)) {
            $format = 'webp';
        }

        return "https://cdn.discordapp.com/app-icons/{$this->id}/{$this->attributes['cover_image']}.{$format}?size={$size}";
    }

    /**
     * Returns the application cover image attribute.
     *
     * @return string|null The application cover image hash or null.
     */
    protected function getCoverImageHashAttribute(): ?string
    {
        return $this->attributes['cover_image'];
    }

    /**
     * Returns the install params attribute.
     *
     * @return InstallParams|null The install params or null.
     */
    protected function getInstallParamsAttribute(): ?InstallParams
    {
        return $this->attributePartHelper('install_params', InstallParams::class);
    }

    /**
     * Returns the invite URL for the application.
     *
     * @param Permission|int $permissions Permissions to set.
     *
     * @return string Invite URL.
     */
    public function getInviteURLAttribute($permissions = 0): string
    {
        if ($permissions instanceof Permission) {
            $permissions = $permissions->bitwise;
        }

        return "https://discord.com/oauth2/authorize?client_id={$this->id}&scope=bot&permissions={$permissions}";
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'application_id' => $this->id,
        ];
    }
}
