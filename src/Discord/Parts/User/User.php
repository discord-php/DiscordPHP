<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Discord\Builders\MessageBuilder;
use Discord\Helpers\BigInt;
use Discord\Http\Endpoint;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Message\AllowedMentions;
use Discord\Repository\UserRepository;
use React\Promise\PromiseInterface;
use Stringable;

use function React\Promise\resolve;

/**
 * A user is a general user that is not attached to a guild.
 *
 * @link https://discord.com/developers/docs/resources/user
 *
 * @since 2.0.0
 *
 * @property      string                     $id                     The unique identifier of the user.
 * @property      string                     $username               The username of the user.
 * @property      string                     $discriminator          The discriminator of the user.
 * @property      ?string|null               $global_name            The user's display name, if it is set. For bots, this is the application name.
 * @property-read string                     $displayname            The display name of the client.
 * @property      ?string|null               $avatar                 The avatar URL of the user.
 * @property-read string|null                $avatar_hash            The avatar hash of the user.
 * @property      bool|null                  $bot                    Whether the user is a bot.
 * @property      bool|null                  $system                 Whether the user is a Discord system user.
 * @property      bool|null                  $mfa_enabled            Whether MFA is enabled.
 * @property      ?string|null               $banner                 The banner URL of the user.
 * @property-read string|null                $banner_hash            The banner hash of the user.
 * @property      ?int|null                  $accent_color           The user's banner color encoded as an integer representation of hexadecimal color code.
 * @property      string|null                $locale                 User locale.
 * @property      bool|null                  $verified               Whether the user is verified.
 * @property      ?string|null               $email                  User email.
 * @property      int|null                   $flags                  User flags.
 * @property      int|null                   $premium_type           Type of nitro subscription.
 * @property      int|null                   $public_flags           Public flags on the user.
 * @property-read string|null                $avatar_decoration      The user's avatar decoration URL.
 * @property-read string|null                $avatar_decoration_hash The user's avatar decoration hash.
 * @property      ?AvatarDecorationData|null $avatar_decoration_data Data for the user's avatar decoration.
 * @property      DisplayNameStyles|null     $display_name_styles    Data for the user's display name styles.
 * @property      ?Collectibles|null         $collectibles           Data for the user's collectibles.
 * @property      ?PrimaryGuild|null         $primary_guild          The user's primary guild
 *
 * @method PromiseInterface<Message> sendMessage(MessageBuilder $builder)
 */
class User extends Part implements Stringable
{
    /** Discord Employee. */
    public const FLAG_STAFF = (1 << 0);
    /** @deprecated 10.36.32 use `User::FLAG_STAFF` */
    public const FLAG_DISCORD_EMPLOYEE = self::FLAG_STAFF;
    /** Partnered Server Owner. */
    public const FLAG_PARTNER = (1 << 1);
    /** @deprecated 10.36.32 use `User::FLAG_PARTNER` */
    public const FLAG_DISCORD_PARTNER = self::FLAG_PARTNER;
    /** HypeSquad Events Member. */
    public const FLAG_HYPESQUAD = (1 << 2);
    /** @deprecated 10.36.32 use `User::FLAG_HYPESQUAD` */
    public const FLAG_HYPESQUAD_EVENTS = (1 << 2);
    /** Bug Hunter Level 1. */
    public const FLAG_BUG_HUNTER_LEVEL_1 = (1 << 3);
    /** House Bravery Member. */
    public const HYPESQUAD_ONLINE_HOUSE_1 = (1 << 6);
    /** House Bravery Member. */
    public const FLAG_HOUSE_BRAVERY = self::HYPESQUAD_ONLINE_HOUSE_1;
    /** House Brilliance Member. */
    public const FLAG_HYPESQUAD_ONLINE_HOUSE_2 = (1 << 7);
    /** House Brilliance Member. */
    public const FLAG_HOUSE_BRILLIANCE = self::FLAG_HYPESQUAD_ONLINE_HOUSE_2;
    /** House Balance Member. */
    public const FLAG_HYPESQUAD_ONLINE_HOUSE_3 = (1 << 8);
    /** House Balance Member. */
    public const FLAG_HOUSE_BALANCE = self::FLAG_HYPESQUAD_ONLINE_HOUSE_3;
    /** Early Nitro Supporter. */
    public const FLAG_PREMIUM_EARLY_SUPPORTER = (1 << 9);
    /** @deprecated 10.36.32 use `User::FLAG_PREMIUM_EARLY_SUPPORTER` */
    public const FLAG_EARLY_SUPPORTER = (1 << 9);
    /** User is a team. */
    public const FLAG_TEAM_PSEUDO_USER = (1 << 10);
    /** @deprecated 10.36.32 use `User::FLAG_TEAM_PSEUDO_USER` */
    public const FLAG_TEAM_USER = (1 << 10);
    /** @todo Undocumented. */
    public const FLAG_SYSTEM = (1 << 12);
    /** Bug Hunter Level 2. */
    public const FLAG_BUG_HUNTER_LEVEL_2 = (1 << 14);
    /** Verified Bot. */
    public const FLAG_VERIFIED_BOT = (1 << 16);
    /** Early Verified Bot Developer. */
    public const FLAG_VERIFIED_DEVELOPER = (1 << 17);
    /** @deprecated 10.36.32 use `User::FLAG_VERIFIED_DEVELOPER` */
    public const FLAG_VERIFIED_BOT_DEVELOPER = self::FLAG_VERIFIED_DEVELOPER;
    /** Moderator Programs Alumni. */
    public const FLAG_CERTIFIED_MODERATOR = (1 << 18);
    /** @deprecated 10.36.32 use `User::FLAG_CERTIFIED_MODERATOR` */
    public const FLAG_DISCORD_CERTIFIED_MODERATOR = self::FLAG_CERTIFIED_MODERATOR;
    /** Bot uses only HTTP interactions and is shown in the online member list. */
    public const FLAG_BOT_HTTP_INTERACTIONS = (1 << 19);
    /** @todo Undocumented. */
    public const FLAG_SUSPECTED_SPAM = (1 << 20); // Not documented
    /** User is an Active Developer. */
    public const FLAG_ACTIVE_DEVELOPER = (1 << 22);

    public const PREMIUM_NONE = 0;
    public const PREMIUM_NITRO_CLASSIC = 1;
    public const PREMIUM_NITRO = 2;
    public const PREMIUM_NITRO_BASIC = 3;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'username',
        'discriminator',
        'global_name',
        'avatar',
        'bot',
        'system',
        'mfa_enabled',
        'banner',
        'accent_color',
        'locale',
        'verified',
        'email',
        'flags',
        'premium_type',
        'public_flags',
        'avatar_decoration_data',
        'collectibles',
        'primary_guild',
    ];

    /**
     * Gets the private channel for the user.
     *
     * @link https://discord.com/developers/docs/resources/user#create-dm
     *
     * @return PromiseInterface<Channel>
     */
    public function getPrivateChannel(): PromiseInterface
    {
        if ($channel = $this->discord->private_channels->get('recipient_id', $this->id)) {
            return resolve($channel);
        }

        return $this->http->post(Endpoint::USER_CURRENT_CHANNELS, ['recipient_id' => $this->id])->then(function ($response) {
            $channel = $this->discord->private_channels->create($response, true);
            $this->discord->private_channels->pushItem($channel);

            return $channel;
        });
    }

    /**
     * Sends a message to the user.
     *
     * Takes a `MessageBuilder` or content of the message for the first parameter. If the first parameter
     * is an instance of `MessageBuilder`, the rest of the arguments are disregarded.
     *
     * @link https://discord.com/developers/docs/resources/channel#create-message
     *
     * @param MessageBuilder|string                 $message          The message builder that should be converted into a message, or the string content of the message.
     * @param bool                                  $tts              Whether the message is TTS.
     * @param \Discord\Parts\Embed\Embed|array|null $embed            An embed object or array to send in the message.
     * @param AllowedMentions|array|null            $allowed_mentions Allowed mentions object for the message.
     * @param Message|null                          $replyTo          Sends the message as a reply to the given message instance.
     *
     * @return PromiseInterface<Message>
     */
    public function sendMessage($message, bool $tts = false, $embed = null, $allowed_mentions = null, ?Message $replyTo = null): PromiseInterface
    {
        return $this
            ->getPrivateChannel()
            ->then(fn (Channel $channel) => $channel->sendMessage($message, $tts, $embed, $allowed_mentions, $replyTo));
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @link https://discord.com/developers/docs/resources/channel#trigger-typing-indicator
     *
     * @throws \RuntimeException
     *
     * @return PromiseInterface
     */
    public function broadcastTyping(): PromiseInterface
    {
        return $this->getPrivateChannel()->then(fn (Channel $channel) => $channel->broadcastTyping());
    }

    /**
     * Returns the display name of the client.
     *
     * @return string Either global_name or username with optional #discriminator.
     */
    protected function getDisplaynameAttribute(): string
    {
        return $this->global_name ?? $this->username.($this->discriminator !== '0' ? '#'.$this->discriminator : '');
    }

    /**
     * Returns the avatar URL for the client.
     *
     * @param string|null $format The image format.
     * @param int         $size   The size of the image.
     *
     * @return string The URL to the client's avatar.
     */
    public function getAvatarAttribute(?string $format = null, int $size = 1024): string
    {
        if (empty($this->attributes['avatar'])) {
            $avatarDiscrim = (($this->discriminator) ? $this->discriminator % 5 : BigInt::shiftRight($this->id, 22) % 6);

            return "https://cdn.discordapp.com/embed/avatars/{$avatarDiscrim}.png?size={$size}";
        }

        if (isset($format)) {
            static $allowed = ['png', 'jpg', 'webp', 'gif'];
            $format = strtolower($format);
            if (! in_array($format, $allowed)) {
                $format = 'webp';
            }
        } elseif (strpos($this->attributes['avatar'], 'a_') === 0) {
            $format = 'gif';
        } else {
            $format = 'webp';
        }

        // Clamp size to allowed powers of two between 16 and 4096
        $size = max(16, min(4096, $size));
        $size = 2 ** (int) round(log($size, 2));

        return "https://cdn.discordapp.com/avatars/{$this->id}/{$this->attributes['avatar']}.{$format}?size={$size}";
    }

    /**
     * Returns the avatar hash for the client.
     *
     * @return string|null The client avatar's hash.
     */
    public function getAvatarHashAttribute(): ?string
    {
        return $this->attributes['avatar'];
    }

    /**
     * Returns the banner URL for the client.
     *
     * @param string|null $format The image format.
     * @param int         $size   The size of the image.
     *
     * @return string|null The URL to the clients banner.
     */
    public function getBannerAttribute(?string $format = null, int $size = 600): ?string
    {
        if (empty($this->attributes['banner'])) {
            return null;
        }

        if (isset($format)) {
            static $allowed = ['png', 'jpg', 'webp', 'gif'];

            if (! in_array(strtolower($format), $allowed)) {
                $format = 'png';
            }
        } elseif (strpos($this->attributes['banner'], 'a_') === 0) {
            $format = 'gif';
        } else {
            $format = 'png';
        }

        return "https://cdn.discordapp.com/banners/{$this->id}/{$this->attributes['banner']}.{$format}?size={$size}";
    }

    /**
     * Returns the banner hash for the client.
     *
     * @return string|null The client banner's hash.
     */
    protected function getBannerHashAttribute(): ?string
    {
        return $this->attributes['banner'] ?? null;
    }

    /**
     * Returns the avatar decoration URL for the client.
     *
     * @param string|null $format The image format. (Only 'png' is allowed)
     * @param int         $size   The size of the image.
     *
     * @return string|null The URL to the clients avatar decoration.
     */
    public function getAvatarDecorationAttribute(?string $format = 'png', int $size = 288): ?string
    {
        if (! isset($this->attributes['avatar_decoration_data'])) {
            return null;
        }

        // Clamp size to allowed powers of two between 16 and 4096
        $size = max(16, min(4096, $size));
        $size = 2 ** (int) round(log($size, 2));

        if (! $asset = $this->avatar_decoration_data->asset ?? null) {
            return null;
        }

        return "https://cdn.discordapp.com/avatar-decoration-presets/{$asset}.{$format}?size={$size}";
    }

    /**
     * Returns the avatar decoration hash for the client.
     *
     * @return string|null The client avatar decoration's hash.
     */
    public function getAvatarDecorationHashAttribute(): ?string
    {
        return $this->avatar_decoration_data->asset ?? null;
    }

    /**
     * Returns the avatar decoration data.
     *
     * @return AvatarDecorationData|null The avatar decoration.
     */
    public function getAvatarDecorationDataAttribute(): ?AvatarDecorationData
    {
        return $this->attributePartHelper('avatar_decoration_data', AvatarDecorationData::class);
    }

    /**
     * Returns the display name styles data.
     *
     * @return DisplayNameStyles|null The display name styles data.
     */
    public function getDisplayNameStylesAttribute(): ?DisplayNameStyles
    {
        return $this->attributePartHelper('display_name_styles', DisplayNameStyles::class);
    }

    /**
     * Returns the collectibles for the client.
     *
     * @return Collectibles|null The collectibles data.
     */
    protected function getCollectiblesAttribute(): ?Collectibles
    {
        return $this->attributePartHelper('collectibles', Collectibles::class);
    }

    /**
     * Returns the primary guild for the client.
     */
    protected function getPrimaryGuildAttribute(): ?PrimaryGuild
    {
        return $this->attributePartHelper('primary_guild', PrimaryGuild::class);
    }

    /**
     * Returns a timestamp for when a user's account was created.
     *
     * @return float|null
     */
    public function createdTimestamp()
    {
        return \Discord\getSnowflakeTimestamp($this->id);
    }

    /**
     * Gets the originating repository of the part.
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return UserRepositor The repository.
     */
    public function getRepository(): UserRepository
    {
        return $this->discord->users;
    }

    /**
     * @inheritDoc
     */
    public function save(?string $reason = null): PromiseInterface
    {
        if ($this->id === $this->discord->id) {
            $data = [];
            if ($this->username) {
                $data['username'] = $this->username;
            }
            if ($this->avatar_hash) {
                $data['avatar'] = $this->avatar_hash;
            }
            if ($this->banner_hash) {
                $data['banner'] = $this->banner_hash;
            }

            return $this->discord->users->modifyCurrentUser($data, $reason)->then(function ($user) {
                $this->fill((array) $user);

                return $this;
            });
        }

        return $this->discord->users->save($this, $reason);
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'user_id' => $this->id,
        ];
    }

    /**
     * Returns a formatted mention.
     *
     * @return string A formatted mention.
     */
    public function __toString(): string
    {
        return "<@{$this->id}>";
    }
}
