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

namespace Discord\Parts\Channel\Message;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use JsonSerializable;

/**
 * Represents a message reference object, which points to another message for replies, forwards, pins, etc.
 *
 * @link https://discord.com/developers/docs/resources/message#message-reference-structure
 *
 * @property int|null    $type                Type of reference (0 = DEFAULT, 1 = FORWARD).
 * @property string|null $message_id          ID of the originating message.
 * @property string|null $channel_id          ID of the originating message's channel.
 * @property string|null $guild_id            ID of the originating message's guild.
 * @property bool|null   $fail_if_not_exists  Whether to error if the referenced message doesn't exist (default true).
 *
 * @property-read Message|null        $message  The originating message.
 * @property-read Channel|Thread|null $channel  The originating message's channel.
 * @property-read Guild|null          $guild    The originating message's guild.
 */
class MessageReference extends Part
{
    public const TYPE_DEFAULT = 0;
    public const TYPE_FORWARD = 1;

    /**
     * The type of the message reference.
     *
     * @var int|null
     */
    protected $type;
    /**
     * The ID of the message being referenced.
     *
     * @var string|null
     */
    protected $message_id;
    /**
     * The ID of the channel the message was sent in.
     *
     * @var string|null
     */
    protected $channel_id;
    /**
     * The ID of the guild the message was sent in.
     *
     * @var string|null
     */
    protected $guild_id;
    /**
     * Whether to fail if the message does not exist.
     *
     * @var bool|null
     */
    protected $fail_if_not_exists;

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'message_id',
        'channel_id',
        'guild_id',
        'fail_if_not_exists',
    ];

    /**
     * Gets the message attribute.
     *
     * @return Message|null
     */
    protected function getMessageAttribute(): ?Message
    {
        if (!$this->message_id) {
            return null;
        }

        if ($channel = $this->channel) {
            return $channel->messages->get('id', $this->message_id);
        }

        return null;
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel|Thread The channel or thread the message was sent in.
     */
    protected function getChannelAttribute(): ?Part
    {
        if (!$this->channel_id) {
            return null;
        }

        if ($guild = $this->guild) {
            $channels = $guild->channels;
            if ($channel = $channels->get('id', $this->channel_id)) {
                return $channel;
            }

            foreach ($channels as $parent) {
                if ($thread = $parent->threads->get('id', $this->channel_id)) {
                    return $thread;
                }
            }
        }

        // @todo potentially slow
        if ($channel = $this->discord->getChannel($this->channel_id)) {
            return $channel;
        }

        return $this->factory->part(Channel::class, [
            'id' => $this->channel_id,
            'type' => Channel::TYPE_DM,
        ], true);
    }

    /**
     * Returns the guild which the channel that the message was sent in belongs to.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
            return $guild;
        }

        // Workaround for Channel::sendMessage() no guild_id
        if ($this->channel_id) {
            return $this->discord->guilds->find(function (Guild $guild) {
                return $guild->channels->offsetExists($this->channel_id);
            });
        }

        return null;
    }
}
