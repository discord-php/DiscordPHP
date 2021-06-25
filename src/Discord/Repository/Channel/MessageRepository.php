<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Channel;

use Discord\Http\Endpoint;
use Discord\Parts\Channel\Message;
use Discord\Repository\AbstractRepository;

/**
 * Contains messages sent to channels.
 *
 * @see \Discord\Parts\Channel\Message
 * @see \Discord\Parts\Channel\Channel
 */
class MessageRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'get' => Endpoint::CHANNEL_MESSAGE,
        'update' => Endpoint::CHANNEL_MESSAGE,
        'delete' => Endpoint::CHANNEL_MESSAGE,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Message::class;
}
