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
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Message;
use Discord\Parts\Part;

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
    public $creatable = false;

    /**
     * {@inheritdoc}
     */
    public $deletable = false;

    /**
     * {@inheritdoc}
     */
    public $editable = false;

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'username', 'avatar', 'discriminator'];

    /**
     * {@inheritdoc}
     */
    protected $uris = [
        'get' => 'users/:id',
    ];

    /**
     * Sends a message to the user.
     *
     * @param string $text The text to send in the message.
     * @param bool   $tts  Whether the message should be sent with text to speech enabled.
     *
     * @return Message The Message that was sent.
     */
    public function sendMessage($message, $tts = false)
    {
        if ($channelID = Cache::get("user.{$this->id}.pm")) {
            $channel_id = $channelID;
        } else {
            $channel = Guzzle::post(
                'users/@me/channels',
                [
                    'recipient_id' => $this->id,
                ]
            );

            $channel_id = $channel->id;
            Cache::set("user.{$this->id}.pm", $channel->id);
        }

        $request = Guzzle::post(
            "channels/{$channel_id}/messages",
            [
                'content' => $message,
                'tts'     => $tts,
            ]
        );

        $message = new Message((array) $request, true);

        Cache::set("message.{$message->id}", $message);

        return $message;
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @return bool Whether the request succeeded or failed.
     */
    public function broadcastTyping()
    {
        if ($channelID = Cache::get("user.{$this->id}.pm")) {
            $channel_id = $channelID;
        } else {
            $channel = Guzzle::post(
                'users/@me/channels',
                [
                    'recipient_id' => $this->id,
                ]
            );

            $channel_id = $channel->id;
            Cache::set("user.{$this->id}.pm", $channel->id);
        }

        Guzzle::post("channels/{$channel_id}/typing");

        return true;
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
