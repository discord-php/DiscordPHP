<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel\Message;

use Discord\Parts\Channel\Message;
use Discord\Parts\Part;

/**
 * The message associated with the message_reference. This is a minimal subset of fields in a message (e.g. author is excluded.).
 *
 * The current subset of message fields consists of: type, content, embeds, attachments, timestamp, edited_timestamp, flags, mentions, mention_roles, stickers, sticker_items, and components.
 *
 * @link https://discord.com/developers/docs/resources/message#message-snapshot-object
 *
 * @since 10.22.0
 *
 * @property Message $message Minimal subset of fields in the forwarded message (partial).
 */
class MessageSnapshot extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'message',
    ];

    /**
     * Returns the message associated with the message_reference.
     *
     * @return ?Message
     */
    protected function getMessageAttribute(): ?Message
    {
        return $this->attributePartHelper('message', Message::class);
    }
}
