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

use Discord\Builders\ChannelBuilder;
use Discord\Http\Endpoint;
use Discord\Parts\Channel\Channel;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;

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
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_CHANNELS,
        'get' => Endpoint::CHANNEL,
        'create' => Endpoint::GUILD_CHANNELS,
        'update' => Endpoint::CHANNEL,
        'delete' => Endpoint::CHANNEL,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Channel::class;

    /**
     * @param MessageBuilder|string $channel The Channel builder that should be converted into a channel, or the name of the channel.
     * @param string|null           $reason  Reason for Audit Log.
     */
    public function createChannel($channel, ?string $reason = null): PromiseInterface
    {
        if (is_string($channel)) {
            $channel = ChannelBuilder::new($channel)->setName($channel);
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->post(new Endpoint($this->endpoints['create']), $channel->jsonSerialize(), $headers)->then(function ($response) {
            $newPart = $this->factory->create($this->class, (array) $response, true);
            $newPart->created = true;

            return $this->cache->set($newPart->{$this->discrim}, $this->factory->create($this->class, (array) $response, true))->then(fn ($success) => $newPart);
        });
    }
}
