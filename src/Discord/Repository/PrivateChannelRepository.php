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

namespace Discord\Repository;

use Discord\Parts\Channel\Channel;
use Discord\Http\Endpoint;
use React\Promise\PromiseInterface;

/**
 * Contains private channels and groups that the client has access to.
 *
 * @see Channel
 *
 * @since 4.0.0
 *
 * @method Channel|null get(string $discrim, $key)
 * @method Channel|null pull(string|int $key, $default = null)
 * @method Channel|null first()
 * @method Channel|null last()
 * @method Channel|null find(callable $callback)
 */
class PrivateChannelRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'get' => Endpoint::CHANNEL,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Channel::class;

    /**
     * Fires a Channel Update Gateway event.
     *
     * @link https://discord.com/developers/docs/resources/channel#modify-channel-json-params-group-dm
     *
     * @param Channel|string $channel
     * @param array          $params
     *
     * @return PromiseInterface
     *
     * @since 10.40.0
     */
    public function modifyGroupDM($channel, array $params = []): PromiseInterface
    {
        if (! is_string($channel)) {
            $channel = $channel->id;
        }

        $allowed = ['name', 'icon'];
        $params = array_filter(
            $params,
            fn ($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($params)) {
            throw new \InvalidArgumentException('No valid parameters to update.');
        }

        return $this->http->patch(EndPoint::bind(Endpoint::CHANNEL, $channel), $params);
    }
}
