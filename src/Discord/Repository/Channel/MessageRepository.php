<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Channel;

use Discord\Parts\Channel\Message;
use Discord\Repository\AbstractRepository;

/**
 * Contains messages sent to channels.
 *
 * @see Discord\Parts\Channel\Message
 * @see Discord\Parts\Channel\Channel
 */
class MessageRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'get'    => 'channels/:channel_id/messages/:id',
        'create' => 'channels/:channel_id/messages',
        'update' => 'channels/:channel_id/messages/:id',
        'delete' => 'channels/:channel_id/messages/:id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $part = Message::class;
}
