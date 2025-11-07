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

namespace Discord\Repository\Channel;

use Discord\Builders\MessageBuilder;
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

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
 *
 * @method Message|null freshen(array $queryparams = []) Messages returned are in order from newest to oldest.
 */
class MessageRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'get' => Endpoint::CHANNEL_MESSAGE,
        'update' => Endpoint::CHANNEL_MESSAGE,
        'delete' => Endpoint::CHANNEL_MESSAGE,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Message::class;

    /**
     * @inheritDoc
     */
    public function __construct($discord, array $vars = [])
    {
        unset($vars['thread_id']); // For thread
        parent::__construct($discord, $vars);
    }

    /**
     * Attempts to create a message in a channel.
     *
     * @since 10.41.0
     *
     * @link https://discord.com/developers/docs/resources/message#create-message
     *
     * @param Channel|string $channel Channel ID or Channel object.
     * @param MessageBuilder $message MessageBuilder instance.
     * @param string|null    $reason  Optional audit log reason.

     * @return PromiseInterface<Message>
     */
    public function build($channel, MessageBuilder $message, ?string $reason = null): PromiseInterface
    {
        if (! is_string($channel)) {
            if (method_exists($channel, 'getBotPermissions')) {
                $botperms = $channel->getBotPermissions();
                if ($botperms && ! $botperms->send_messages) {
                    return reject(new NoPermissionsException("You do not have permission to send messages in channel {$channel->id}."));
                }
            }
            $channelId = $channel->id;
        } else {
            $channelId = $channel;
        }

        $headers = [];
        if ($reason !== null) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        $endpoint = Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $channelId);

        if ($message->requiresMultipart()) {
            $multipart = $message->toMultipart();

            return $this->http->post($endpoint, (string) $multipart, array_merge($headers, $multipart->getHeaders()))
                ->then(fn ($response) => $this->factory->part($this->class, (array) $response, true));
        }

        return $this->http->post($endpoint, $message->jsonSerialize(), $headers)
            ->then(fn ($response) => $this->factory->part($this->class, (array) $response, true));
    }
}
