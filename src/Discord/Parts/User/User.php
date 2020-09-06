<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Part;
use React\Promise\Deferred;

/**
 * A user is a general user that is not attached to a guild.
 *
 * @property string $id            The unique identifier of the user.
 * @property string $username      The username of the user.
 * @property string $avatar        The avatar URL of the user.
 * @property string $avatar_hash   The avatar hash of the user.
 * @property string $discriminator The discriminator of the user.
 * @property bool   $bot           Whether the user is a bot.
 * @property bool $system Whether the user is a Discord system user.
 * @property bool $mfa_enabled Whether MFA is enabled.
 * @property string $locale User locale.
 * @property bool $verified Whether the user is verified.
 * @property string $email User email.
 * @property int $flags User flags.
 * @property int $premium_type Type of nitro subscription.
 * @property int $public_flags Public flags on the user.
 */
class User extends Part
{
    const FLAG_DISCORD_EMPLOYEE = (1 << 0);
    const FLAG_DISCORD_PARTNER = (1 << 1);
    const FLAG_HYPESQUAD_EVENTS = (1 << 2);
    const FLAG_BUG_HUNTER_LEVEL_1 = (1 << 3);
    const FLAG_HOUSE_BRAVERY = (1 << 6);
    const FLAG_HOUSE_BRILLIANCE = (1 << 7);
    const FLAG_HOUSE_BALANCE = (1 << 8);
    const FLAG_EARLY_SUPPORTER = (1 << 9);
    const FLAG_TEAM_USER = (1 << 10);
    const FLAG_SYSTEM = (1 << 12);
    const FLAG_BUG_HUNTER_LEVEL_2 = (1 << 14);
    const FLAG_VERIFIED_BOT = (1 << 16);
    const FLAG_VERIFIED_BOT_DEVELOPER = (1 << 17);

    const PREMIUM_NONE = 0;
    const PREMIUM_NITRO_CLASSIC = 1;
    const PREMIUM_NITRO = 2;

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'username', 'avatar', 'discriminator', 'bot', 'system', 'mfa_enabled', 'locale', 'verified', 'email', 'flags', 'premium_type', 'public_flags'];

    /**
     * Gets the private channel for the user.
     *
     * @return \React\Promise\Promise
     */
    public function getPrivateChannel()
    {
        $deferred = new Deferred();

        if ($channel = $this->discord->private_channels->get('id', $this->id)) {
            $deferred->resolve($channel);
        } else {
            $this->http->post('users/@me/channels', ['recipient_id' => $this->id])->then(function ($response) use ($deferred) {
                $channel = $this->factory->create(Channel::class, $response, true);
                $this->discord->private_channels->push($channel);

                $deferred->resolve($channel);
            }, \React\Partial\bind([$deferred, 'reject']));
        }

        return $deferred->promise();
    }

    /**
     * Sends a message to the user.
     *
     * @param string $text  The text to send in the message.
     * @param bool   $tts   Whether the message should be sent with text to speech enabled.
     * @param Embed  $embed An embed to send.
     *
     * @return \React\Promise\Promise
     */
    public function sendMessage($message, $tts = false, $embed = null)
    {
        $deferred = new Deferred();

        $this->getPrivateChannel()->then(function ($channel) use ($message, $tts, $embed, $deferred) {
            $channel->sendMessage($message, $tts, $embed)->then(function ($response) use ($deferred) {
                $message = $this->factory->create(Message::class, $response, true);
                $deferred->resolve($message);
            }, \React\Partial\bind([$deferred, 'reject']));
        }, \React\Partial\bind([$deferred, 'reject']));

        return $deferred->promise();
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @return \React\Promise\Promise
     */
    public function broadcastTyping()
    {
        $deferred = new Deferred();

        $this->getPrivateChannel()->then(function ($channel) use ($deferred) {
            $channel->broadcastTyping()->then(
                \React\Partial\bind([$deferred, 'resolve']),
                \React\Partial\bind([$deferred, 'reject'])
            );
        });

        return $deferred->promise();
    }

    /**
     * Returns the avatar URL for the client.
     *
     * @param string $format The image format.
     * @param int    $size   The size of the image.
     *
     * @return string The URL to the clients avatar.
     */
    public function getAvatarAttribute($format = 'jpg', $size = 1024)
    {
        if (empty($this->attributes['avatar'])) {
            return;
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
    protected function getAvatarHashAttribute()
    {
        return $this->attributes['avatar'];
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
