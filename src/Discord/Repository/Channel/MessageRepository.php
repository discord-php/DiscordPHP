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

use Discord\Discord;
use Discord\Http\Endpoint;
use Discord\Parts\Channel\Message;
use Discord\Repository\AbstractRepository;

/**
 * Contains messages sent to a channel.
 *
 * @see Message
 * @see \Discord\Parts\Channel\Channel
 *
 * @since 4.0.0
 *
 * @method Message|null get(string $discrim, $key)
 * @method Message|null pull(string|int $key, $default = null)
 * @method Message|null first()
 * @method Message|null last()
 * @method Message|null find(callable $callback)
 */
class MessageRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'get' => Endpoint::CHANNEL_MESSAGE,
        'update' => Endpoint::CHANNEL_MESSAGE,
        'delete' => Endpoint::CHANNEL_MESSAGE,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Message::class;

    /**
     * {@inheritDoc}
     */
    public function __construct(Discord $discord, array $vars = [])
    {
        unset($vars['thread_id']); // For thread
        parent::__construct($discord, $vars);
    }
}
