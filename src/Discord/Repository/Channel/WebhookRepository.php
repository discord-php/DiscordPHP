<?php

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