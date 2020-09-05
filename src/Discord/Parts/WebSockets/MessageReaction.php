<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\WebSockets;

use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;
use Discord\Parts\User\Member;

/**
 * Part that defines a message reaction.
 *
 * @property string $user_id
 * @property string $message_id
 * @property Member $member
 * @property Emoji  $emoji
 * @property string $channel_id
 * @property string $guild_id
 * @property \Discord\Parts\Channel\Channel $channel
 * @property \Discord\Parts\Guild\Guild $guild
 * @property \Discord\Parts\User\User $user
 * @property \Discord\Parts\Channel\Message $message
 */
class MessageReaction extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['user_id', 'message_id', 'member', 'emoji', 'channel_id', 'guild_id'];

    /**
     * Gets the user attribute.
     *
     * @return \Discord\Parts\User\User
     */
    public function getUserAttribute()
    {
        if ($member = $this->member) {
            return $member->user;
        } elseif ($user = $this->discord->users->get('id', $this->attributes['user_id'])) {
            return $user;
        }
    }

    /**
     * Gets the message attribute.
     * The bot needs to be set up to store messages
     * to get the full message, otherwise the message
     * object only contains the ID.
     *
     * @return \Discord\Parts\Channel\Message
     */
    public function getMessageAttribute()
    {
        if ($channel = $this->channel) {
            if ($message = $channel->messages->get('id', $this->attributes['message_id'])) {
                return $message;
            }
        }

        return $this->factory->create(Message::class, ['id' => $this->attributes['message_id']], true);
    }

    /**
     * Gets the member attribute.
     *
     * @return \Discord\Parts\User\Member
     */
    public function getMemberAttribute()
    {
        if (isset($this->attributes['user_id']) && $guild = $this->guild) {
            if ($member = $guild->members->get('id', $this->attributes['user_id'])) {
                return $member;
            }
        } elseif (isset($this->attributes['member'])) {
            return $this->factory->create(Member::class, $this->attributes['member'], true);
        }
    }

    /**
     * Gets the emoji attribute.
     *
     * @return \Discord\Parts\Guild\Emoji
     */
    public function getEmojiAttribute()
    {
        if (isset($this->attributes['emoji'])) {
            return $this->factory->create(Emoji::class, $this->attributes['emoji'], true);
        }
    }

    /**
     * Gets the channel attribute.
     *
     * @return \Discord\Parts\Channel\Channel
     */
    public function getChannelAttribute()
    {
        if ($guild = $this->guild) {
            return $guild->channels->get('id', $this->attributes['channel_id']);
        }

        return $this->discord->private_channels->get('id', $this->attributes['channel_id']);
    }

    /**
     * Gets the guild attribute.
     *
     * @return \Discord\Parts\Guild\Guild
     */
    public function getGuildAttribute()
    {
        if (isset($this->attributes['guild_id'])) {
            return $this->discord->guilds->get('id', $this->attributes['guild_id']);
        }
    }
}
