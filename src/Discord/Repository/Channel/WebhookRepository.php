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

use Discord\Parts\Channel\Webhook;
use Discord\Repository\AbstractRepository;

/**
 * {@inheritdoc}
 */
class WebhookRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all' => 'channels/:channel_id/webhooks',
        'create' => 'channels/:channel_id/webhooks',
        'delete' => 'webhooks/:id',
        'update' => 'webhooks/:id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $part = Webhook::class;
}
