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

namespace Discord\Repository\Guild;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Thread\Thread;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Contains channels on a guild.
 *
 * @see Channel
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 4.0.0
 *
 * @method Channel|null get(string $discrim, $key)
 * @method Channel|null pull(string|int $key, $default = null)
 * @method Channel|null first()
 * @method Channel|null last()
 * @method Channel|null find(callable $callback)
 */
class ChannelRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_CHANNELS,
        'get' => Endpoint::CHANNEL,
        'create' => Endpoint::GUILD_CHANNELS,
        'update' => Endpoint::CHANNEL,
        'delete' => Endpoint::CHANNEL,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Channel::class;

    /**
     * Gets the pinned messages in the channel.
     *
     * @link https://discord.com/developers/docs/resources/message#get-channel-pins
     *
     * @param Channel|Thread|string $channel           The channel to get the pinned messages from.
     * @param int                   $options['limit']  The amount of messages to retrieve.
     * @param Message|Carbon|string $options['before'] A message or timestamp to get messages before.
     *
     * @return PromiseInterface<Collection<Message>>
     *
     * @since 10.19.0
    */
    public function getPinnedMessages($channel, array $options = []): PromiseInterface
    {
        if ($channel instanceof Channel || $channel instanceof Thread) {
            $channel = $channel->id;
        }

        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults(['limit' => 50])
            ->setDefined(['before', 'limit'])
            ->setAllowedTypes('before', [Carbon::class, 'string'])
            ->setAllowedTypes('limit', 'integer')
            ->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 50))
            ->setDefault('before', null);

        $options = $resolver->resolve($options);

        if (isset($options['before'])) {
            if ($options['before'] instanceof Message) {
                $options['before'] = $options['before']->timestamp;
            }
        }

        return $this->http->get(Endpoint::bind(Endpoint::CHANNEL_MESSAGES_PINS, $channel), $options)
        ->then(function ($responses) {
            $messages = Collection::for(Message::class);

            foreach ($responses as $response) {
                $messages->pushItem($this->messages->get('id', $response->id) ?: $this->messages->create($response, true));
            }

            return $messages;
        });
    }
}
