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

use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * Represents a specific reaction to a message by a specific user.
 * Different from `Reaction` in the fact that `Reaction` represents a specific reaction
 * to a message by _multiple_ members.
 *
 * @property string $reaction_id
 * @property string  $user_id
 * @property string  $message_id
 * @property Member  $member
 * @property Emoji   $emoji
 * @property string  $channel_id
 * @property string  $guild_id
 * @property Channel $channel
 * @property Guild   $guild
 * @property User    $user
 * @property Message $message
 */
class MessageReaction extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['user_id', 'message_id', 'member', 'emoji', 'channel_id', 'guild_id'];

    /**
     * Gets the ID of the reaction.
     *
     * @return string
     */
    protected function getReactionIdAttribute(): string
    {
        return ":{$this->emoji->name}:{$this->emoji->id}";
    }

    /**
     * Gets the user attribute.
     *
     * @return User
     */
    protected function getUserAttribute(): ?User
    {
        if ($member = $this->member) {
            return $member->user;
        } elseif ($user = $this->discord->users->get('id', $this->attributes['user_id'])) {
            return $user;
        }

        return null;
    }

    /**
     * Gets the message attribute.
     * The bot needs to be set up to store messages
     * to get the full message, otherwise the message
     * object only contains the ID.
     *
     * @return Message
     * @throws \Exception
     */
    protected function getMessageAttribute(): Message
    {
        if ($channel = $this->channel) {
            if ($message = $channel->messages->get('id', $this->attributes['message_id'])) {
                return $message;
            }
        }

        return $this->factory->create(Message::class, [
            'id' => $this->attributes['message_id'],
            'channel_id' => $this->attributes['channel_id'],
        ], true);
    }

    /**
     * Gets the member attribute.
     *
     * @return Member
     * @throws \Exception
     */
    protected function getMemberAttribute(): ?Member
    {
        if (isset($this->attributes['user_id']) && $guild = $this->guild) {
            if ($member = $guild->members->get('id', $this->attributes['user_id'])) {
                return $member;
            }
        } elseif (isset($this->attributes['member'])) {
            return $this->factory->create(Member::class, $this->attributes['member'], true);
        }

        return null;
    }

    /**
     * Gets the emoji attribute.
     *
     * @return Emoji
     * @throws \Exception
     */
    protected function getEmojiAttribute(): ?Emoji
    {
        if (isset($this->attributes['emoji'])) {
            return $this->factory->create(Emoji::class, $this->attributes['emoji'], true);
        }

        return null;
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel
     */
    protected function getChannelAttribute(): ?Channel
    {
        if ($guild = $this->guild) {
            return $guild->channels->get('id', $this->attributes['channel_id']);
        }

        return $this->discord->private_channels->get('id', $this->attributes['channel_id']);
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (isset($this->attributes['guild_id'])) {
            return $this->discord->guilds->get('id', $this->attributes['guild_id']);
        }

        return null;
    }
}
