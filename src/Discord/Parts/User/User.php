<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Discord\Cache\Cache;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Part;
use React\Promise\Deferred;

/**
 * A user is a general user that is not attached to a guild.
 *
 * @property string $id
 * @property string $username
 * @property string $avatar
 * @property string $discriminator
 */
class User extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'username', 'avatar', 'discriminator'];

    /**
     * Sends a message to the user.
     *
     * @param string $text The text to send in the message.
     * @param bool   $tts  Whether the message should be sent with text to speech enabled.
     *
     * @return \React\Promise\Promise
     */
    public function sendMessage($message, $tts = false)
    {
        $deferred = new Deferred();
        $getChannelID = new Deferred();

        $getChannelID->promise()->then(function ($channel) use ($message, $tts, $deferred) {
            $channel->sendMessage($message, $tts)->then(
                function ($response) use ($deferred) {
                    $message = $this->factory->create(Message::class, $response, true);
                    $this->cache->set("message.{$message->id}", $message);
                    $deferred->resolve($message);
                },
                \React\Partial\bind_right($this->reject, $deferred)
            );
        });

        if ($this->cache->has("pm_channel.{$this->id}")) {
            $getChannelID->resolve($this->cache->get("pm_channel.{$this->id}"));
        } else {
            $this->http->post('users/@me/channels', ['recipient_id' => $this->id])->then(function ($response) use ($getChannelID) {
                $channel = $this->factory->create(Channel::class, $response, true);
                $this->cache->set("pm_channel.{$this->id}", $channel);

                $getChannelID->resolve($channel);
            }, \React\Partial\bind_right($this->reject, $getChannelID));
        }

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
        $getChannelID = new Deferred();

        $getChannelID->promise()->then(function ($channel) use ($deferred) {
            $channel->broadcastTyping()->then(
                \React\Partial\bind_right($this->resolve, $deferred),
                \React\Partial\bind_right($this->reject, $deferred)
            );
        });

        if ($this->cache->has("pm_channel.{$this->id}")) {
            $getChannelID->resolve($this->cache->get("pm_channel.{$this->id}"));
        } else {
            $this->http->post('users/@me/channels', ['recipient_id' => $this->id])->then(function ($response) use ($getChannelID) {
                $channel = $this->factory->create(Channel::class, $response, true);
                $this->cache->set("pm_channel.{$this->id}", $channel);

                $getChannelID->resolve($channel);
            }, \React\Partial\bind_right($this->reject, $getChannelID));
        }

        return $deferred->promise();
    }

    /**
     * Returns the avatar URL for the client.
     *
     * @return string The URL to the clients avatar.
     */
    public function getAvatarAttribute()
    {
        if (empty($this->attributes['avatar'])) {
            return;
        }

        return "https://discordapp.com/api/users/{$this->id}/avatars/{$this->attributes['avatar']}.jpg";
    }

    /**
     * Returns the avatar ID for the client.
     *
     * @return string The client avatar's hash.
     */
    public function getAvatarIdAttribute()
    {
        return $this->avatar;
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
