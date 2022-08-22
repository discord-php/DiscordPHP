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
 * Contains messages sent to channels from webhook.
 *
 * @see Message
 * @see \Discord\Parts\Channel\Channel
 * @see \Discord\Parts\Channel\Webhook
 */
class WebhookMessageRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'get' => Endpoint::WEBHOOK_MESSAGE,
        'update' => Endpoint::WEBHOOK_MESSAGE,
        'delete' => Endpoint::WEBHOOK_MESSAGE,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Message::class;
}
