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

use Discord\Builders\Builder;
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
     * Attempts to save a channel to the Discord servers.
     *
     * @link https://discord.com/developers/docs/resources/guild#create-guild-channel
     *
     * @since 10.25.0
     *
     * @param Guild|string                  $guild   The guild or guild ID that the channel should be created on.
     * @param Channel|ChannelBuilder|string $channel The Channel builder that should be converted into a channel, or the name of the channel.
     * @param string|null                   $reason  Reason for Audit Log.
     *
     * @return PromiseInterface<Channel>
     */
    public function createChannel($guild, $channel, ?string $reason = null): PromiseInterface
    {
        if (! is_string($guild)) {
            $guild = $guild->id;
        }

        if (is_string($channel)) {
            $channel = ChannelBuilder::new($channel)->setName($channel);
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->post(Endpoint::bind(Endpoint::GUILD_CHANNELS, $guild), $channel->jsonSerialize(), $headers)
            ->then(function ($response) {
                if ($channelPart = $this->get('id', $response->id)) {
                    $channelPart->fill((array) $response);
                    $channelPart->created = true;
                } else {
                    $channelPart = $this->create($response, true);
                }

                return $this->cache->set($channelPart->{$this->discrim}, $channelPart)->then(fn ($success) => $channelPart);
            });
    }
}
