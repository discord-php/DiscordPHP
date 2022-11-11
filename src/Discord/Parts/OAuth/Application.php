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
 * @see https://discord.com/developers/docs/resources/application
 *
 * @property string                  $id                     The client ID of the OAuth application.
 * @property string                  $name                   The name of the OAuth application.
 * @property string|null             $icon                   The icon URL of the OAuth application.
 * @property string                  $icon_hash              The icon hash of the OAuth application.
 * @property string                  $description            The description of the OAuth application.
 * @property string[]                $rpc_origins            An array of RPC origin URLs.
 * @property bool                    $bot_public             When false only app owner can join the app's bot to guilds.
 * @property bool                    $bot_require_code_grant When true the app's bot will only join upon completion of the full oauth2 code grant flow.
 * @property string|null             $terms_of_service_url   The url of the app's terms of service.
 * @property string|null             $privacy_policy_url     The url of the app's privacy policy
 * @property User|null               $owner                  The owner of the OAuth application.
 * @property string                  $verify_key             The hex encoded key for verification in interactions and the GameSDK's GetTicket.
 * @property object|null             $team                   If the application belongs to a team, this will be a list of the members of that team.
 * @property string|null             $guild_id               If this application is a game sold on Discord, this field will be the guild to which it has been linked.
 * @property string|null             $primary_sku_id         If this application is a game sold on Discord, this field will be the id of the "Game SKU" that is created, if exists.
 * @property string|null             $slug                   If this application is a game sold on Discord, this field will be the URL slug that links to the store page.
 * @property string|null             $cover_image            The application's default rich presence invite cover image URL.
 * @property string|null             $cover_image_hash       The application's default rich presence invite cover image hash.
 * @property int                     $flags                  The application's public flags.
 * @property string[]|null           $tags                   Up to 5 tags describing the content and functionality of the application.
 * @property object|null             $install_params         Settings for the application's default in-app authorization link, if enabled.
 * @property string|null             $custom_install_url     The application's default custom authorization link, if enabled.
 * @property string                  $invite_url             The invite URL to invite the bot to a guild.
 * @property GlobalCommandRepository $commands               The application global commands.
 */
class Application extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'bot_public',
        'bot_require_code_grant',
        'cover_image',
        'description',
        'guild_id',
        'icon',
        'id',
        'name',
        'owner',
        'primary_sku_id',
        'slug',
        'summary', // deprecated, is now empty, used to be same as description
        'team',
        'verify_key',
        'rpc_origins',
        'terms_of_service_url',
        'privacy_policy_url',
        'flags',
        'tags',
        'install_params',
        'custom_install_url',
    ];

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

    /**
     * @inheritdoc
     */
    protected $repositories = [
        'commands' => GlobalCommandRepository::class,
    ];

    /**
     * Returns the application icon.
     *
     * @param string|null $format The image format.
     * @param int         $size   The size of the image.
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
     * Returns the owner of the application.
     *
     * @return User|null Owner of the application.
     */
    protected function getOwnerAttribute(): ?User
    {
        if (! isset($this->attributes['owner'])) {
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
     * @param string|null $format The image format.
     * @param int         $size   The size of the image.
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

        return "https://discordapp.com/oauth2/authorize?client_id={$this->id}&scope=bot&permissions={$permissions}";
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'application_id' => $this->id,
        ];
    }
}
