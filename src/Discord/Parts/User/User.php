<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Discord\Http\Endpoint;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Part;
use Discord\Parts\Channel\Message;
use React\Promise\ExtendedPromiseInterface;

/**
 * A user is a general user that is not attached to a guild.
 *
 * @property string $id            The unique identifier of the user.
 * @property string $username      The username of the user.
 * @property string $avatar        The avatar URL of the user.
 * @property string $avatar_hash   The avatar hash of the user.
 * @property string $discriminator The discriminator of the user.
 * @property bool   $bot           Whether the user is a bot.
 * @property bool   $system        Whether the user is a Discord system user.
 * @property bool   $mfa_enabled   Whether MFA is enabled.
 * @property string $locale        User locale.
 * @property bool   $verified      Whether the user is verified.
 * @property string $email         User email.
 * @property int    $flags         User flags.
 * @property int    $premium_type  Type of nitro subscription.
 * @property int    $public_flags  Public flags on the user.
 */
class User extends Part
{
    public const FLAG_DISCORD_EMPLOYEE = (1 << 0);
    public const FLAG_DISCORD_PARTNER = (1 << 1);
    public const FLAG_HYPESQUAD_EVENTS = (1 << 2);
    public const FLAG_BUG_HUNTER_LEVEL_1 = (1 << 3);
    public const FLAG_HOUSE_BRAVERY = (1 << 6);
    public const FLAG_HOUSE_BRILLIANCE = (1 << 7);
    public const FLAG_HOUSE_BALANCE = (1 << 8);
    public const FLAG_EARLY_SUPPORTER = (1 << 9);
    public const FLAG_TEAM_USER = (1 << 10);
    public const FLAG_SYSTEM = (1 << 12);
    public const FLAG_BUG_HUNTER_LEVEL_2 = (1 << 14);
    public const FLAG_VERIFIED_BOT = (1 << 16);
    public const FLAG_VERIFIED_BOT_DEVELOPER = (1 << 17);
    public const FLAG_DISCORD_CERTIFIED_MODERATOR = (1 << 18);

    public const PREMIUM_NONE = 0;
    public const PREMIUM_NITRO_CLASSIC = 1;
    public const PREMIUM_NITRO = 2;

    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'username', 'avatar', 'discriminator', 'bot', 'system', 'mfa_enabled', 'locale', 'verified', 'email', 'flags', 'premium_type', 'public_flags'];

    /**
     * Gets the private channel for the user.
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function getPrivateChannel(): ExtendedPromiseInterface
    {
        if ($channel = $this->discord->private_channels->get('recipient_id', $this->id)) {
            return \React\Promise\resolve($channel);
        }

        return $this->http->post(Endpoint::USER_CURRENT_CHANNELS, ['recipient_id' => $this->id])->then(function ($response) {
            $channel = $this->factory->create(Channel::class, $response, true);
            $this->discord->private_channels->push($channel);

            return $channel;
        });
    }

    /**
     * Sends a message to the user.
     *
     * @param string     $message The text to send in the message.
     * @param bool       $tts     Whether the message should be sent with text to speech enabled.
     * @param Embed|null $embed   An embed to send.
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function sendMessage(string $message, bool $tts = false, ?Embed $embed = null): ExtendedPromiseInterface
    {
        return $this->getPrivateChannel()->then(function ($channel) use ($message, $tts, $embed) {
            return $channel->sendMessage($message, $tts, $embed);
        });
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function broadcastTyping(): ExtendedPromiseInterface
    {
        return $this->getPrivateChannel()->then(function ($channel) {
            return $channel->broadcastTyping();
        });
    }

    /**
     * Returns the avatar URL for the client.
     *
     * @param string $format The image format.
     * @param int    $size   The size of the image.
     *
     * @return string The URL to the clients avatar.
     */
    public function getAvatarAttribute(string $format = 'jpg', int $size = 1024): string
    {
        if (empty($this->attributes['avatar'])) {
            $avatarDiscrim = (int) $this->discriminator % 5;

            return "https://cdn.discordapp.com/embed/avatars/{$avatarDiscrim}.png?size={$size}";
        }

        if (false === array_search($format, ['png', 'jpg', 'webp'])) {
            $format = 'jpg';
        }

        return "https://cdn.discordapp.com/avatars/{$this->id}/{$this->attributes['avatar']}.{$format}?size={$size}";
    }

    /**
     * Returns the avatar hash for the client.
     *
     * @return string The client avatar's hash.
     */
    protected function getAvatarHashAttribute(): string
    {
        return $this->attributes['avatar'];
    }

    /**
     * Returns a timestamp for when a user's account was created.
     *
     * @return float
     */
    public function createdTimestamp()
    {
        return \Discord\getSnowflakeTimestamp($this->id);
    }

    /**
     * @inheritdoc
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
    public function __toString()
    {
        return "<@{$this->id}>";
    }
}
