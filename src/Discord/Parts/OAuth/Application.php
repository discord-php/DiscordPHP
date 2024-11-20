<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\OAuth;

use Discord\Parts\Part;
use Discord\Parts\Permissions\Permission;
use Discord\Parts\User\User;
use Discord\Repository\Interaction\GlobalCommandRepository;

/**
 * The OAuth2 application of the bot.
 *
 * @link https://discord.com/developers/docs/resources/application
 *
 * @since 7.0.0
 *
 * @property string        $id                                The client ID of the OAuth application.
 * @property string        $name                              The name of the OAuth application.
 * @property string|null   $icon                              The icon URL of the OAuth application.
 * @property string        $icon_hash                         The icon hash of the OAuth application.
 * @property string        $description                       The description of the OAuth application.
 * @property string[]      $rpc_origins                       An array of RPC origin URLs.
 * @property bool          $bot_public                        When false only app owner can join the app's bot to guilds.
 * @property bool          $bot_require_code_grant            When true the app's bot will only join upon completion of the full oauth2 code grant flow.
 * @property User|null     $bot                               The partial user object for the bot user associated with the application.
 * @property string|null   $terms_of_service_url              The url of the app's terms of service.
 * @property string|null   $privacy_policy_url                The url of the app's privacy policy
 * @property User|null     $owner                             The owner of the OAuth application.
 * @property string        $verify_key                        The hex encoded key for verification in interactions and the GameSDK's GetTicket.
 * @property object|null   $team                              If the application belongs to a team, this will be a list of the members of that team.
 * @property string|null   $guild_id                          If this application is a game sold on Discord, this field will be the guild to which it has been linked.
 * @property string|null   $primary_sku_id                    If this application is a game sold on Discord, this field will be the id of the "Game SKU" that is created, if exists.
 * @property string|null   $slug                              If this application is a game sold on Discord, this field will be the URL slug that links to the store page.
 * @property string|null   $cover_image                       The application's default rich presence invite cover image URL.
 * @property string|null   $cover_image_hash                  The application's default rich presence invite cover image hash.
 * @property int           $flags                             The application's public flags.
 * @property int|null      $approximate_guild_count           The application's approximate count of the app's guild membership.
 * @property int|null      $approximate_user_install_count    The approximate count of users that have installed the app.
 * @property string[]|null $redirect_uris                     Array of redirect URIs for the application.
 * @property string|null   $interactions_endpoint_url         The interactions endpoint URL for the application.
 * @property string|null   $role_connections_verification_url The application's role connection verification entry point, which when configured will render the app as a verification method in the guild role verification configuration.
 * @property string[]|null $tags                              Up to 5 tags describing the content and functionality of the application.
 * @property object|null   $install_params                    Settings for the application's default in-app authorization link, if enabled.
 * @property int[]|null    $integration_types
 * @property object[]|null $integration_types_config          Default scopes and permissions for each supported installation context. Value for each key is an integration type configuration object.
 * @property string|null   $custom_install_url                The application's default custom authorization link, if enabled.
 *
 * @property string $invite_url The invite URL to invite the bot to a guild.
 *
 * @property GlobalCommandRepository $commands The application global commands.
 */
class Application extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'name',
        'icon',
        'description',
        // 'type',
        'bot',
        // 'is_monetized',
        'guild_id',
        'bot_public',
        'bot_require_code_grant',
        'verify_key',
        'flags',
        // 'hook',
        'redirect_uris',
        'interactions_endpoint_url',
        'role_connections_verification_url',
        'owner',
        'approximate_guild_count',
        'approximate_user_install_count',
        // 'interactions_event_types',
        // 'interactions_version',
        // 'explicit_content_filter',
        // 'rpc_application_state',
        // 'store_application_state',
        // 'verification_state',
        // 'integration_public',
        // 'integration_require_code_grant',
        // 'discoverability_state',
        // 'discovery_eligibility_flags',
        // 'monetization_state',
        // 'monetization_eligibility_flags',
        'team',
        'integration_types',
        'integration_types_config',
        'cover_image',
        'primary_sku_id',
        'slug',
        'rpc_origins',
        'terms_of_service_url',
        'privacy_policy_url',
        'custom_install_url',
        'install_params',
        'tags',
    ];

    public const APPLICATION_AUTO_MODERATION_RULE_CREATE_BADGE = (1 << 6);
    public const GATEWAY_PRESENCE = (1 << 12);
    public const GATEWAY_PRESENCE_LIMITED = (1 << 13);
    public const GATEWAY_GUILD_MEMBERS = (1 << 14);
    public const GATEWAY_GUILD_MEMBERS_LIMITED = (1 << 15);
    public const VERIFICATION_PENDING_GUILD_LIMIT = (1 << 16);
    public const EMBEDDED = (1 << 17);
    public const GATEWAY_MESSAGE_CONTENT = (1 << 18);
    public const GATEWAY_MESSAGE_CONTENT_LIMITED = (1 << 19);
    public const APPLICATION_COMMAND_BADGE = (1 << 23);
    public const ACTIVE = (1 << 24);
    public const INTEGRATION_TYPE_GUILD_INSTALL = 0;
    public const INTEGRATION_TYPE_USER_INSTALL = 1;

    /**
     * {@inheritDoc}
     */
    protected $repositories = [
        'commands' => GlobalCommandRepository::class,
    ];

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

        $allowed = ['png', 'jpg', 'webp'];
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

        return $this->factory->part(User::class, (array) $this->attributes['bot'], true);
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

        return $this->factory->part(User::class, (array) $this->attributes['owner'], true);
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

        $allowed = ['png', 'jpg', 'webp'];
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
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'application_id' => $this->id,
        ];
    }
}
