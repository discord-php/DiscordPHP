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

use Discord\Helpers\Guzzle;
use Discord\Parts\Part;

class User extends Part
{
    /**
     * Is the part creatable?
     *
     * @var bool
     */
    public $creatable = false;

    /**
     * Is the part deletable?
     *
     * @var bool
     */
    public $deletable = false;

    /**
     * Is the part editable?
     *
     * @var bool
     */
    public $editable = false;

    /**
     * The parts fillable attributes.
     *
     * @var array
     */
    protected $fillable = ['id', 'username', 'avatar', 'discriminator'];

    /**
     * URIs used to get/create/update/delete the part.
     *
     * @var array
     */
    protected $uris = [
        'get' => 'users/:id',
    ];

    /**
     * Sends a message to the user.
     *
     * @param string $message
     * @param bool   $tts
     *
     * @return array
     */
    public function sendMessage($message, $tts = false)
    {
        if (isset($this->attributes_cache['channel_id'])) {
            $channel_id = $this->attributes_cache['channel_id'];
        } else {
            $channel = Guzzle::post('users/@me/channels', [
                'recipient_id' => $this->id,
            ]);

            $channel_id = $channel->id;
            $this->attributes_cache['channel_id'] = $channel->id;
        }

        $request = Guzzle::post("channels/{$channel_id}/messages", [
            'content' => $message,
            'tts' => $tts,
        ]);

        return $request;
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @return bool
     */
    public function broadcastTyping()
    {
        if (isset($this->attributes_cache['channel_id'])) {
            $channel_id = $this->attributes_cache['channel_id'];
        } else {
            $channel = Guzzle::post('users/@me/channels', [
                'recipient_id' => $this->id,
            ]);

            $channel_id = $channel->id;
            $this->attributes_cache['channel_id'] = $channel->id;
        }

        Guzzle::post("channels/{$channel_id}/typing");

        return true;
    }

    /**
     * Returns the avatar URL for the client.
     *
     * @return string
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
     * @return string
     */
    public function getAvatarIdAttribute()
    {
        return $this->avatar;
    }

    /**
     * Returns a formatted mention.
     *
     * @return string
     */
    public function __toString()
    {
        return "<@{$this->id}>";
    }
}
