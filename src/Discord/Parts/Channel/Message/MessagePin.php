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

use Carbon\Carbon;
use Discord\Parts\Channel\Message;
use Discord\Parts\Part;

/**
 * An array containing a pinned messages and its pinned_at timestamp.
 *
 * @link https://discord.com/developers/docs/resources/message#message-pin-object
 *
 * @since 10.19.0
 *
 * @property Carbon  $pinned_at The time the message was pinned
 * @property Message $message   The pinned message.
 */
class MessagePin extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'pinned_at',
        'message',
    ];

    protected function getPinnedAtAttribute(): Carbon
    {
        return $this->attributeCarbonHelper('pinned_at');
    }

    protected function getMessageAttribute(): Message
    {
        return $this->attributePartHelper('message', Message::class);
    }
}
