<?php

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
     * {@inheritDoc}
     */
    protected $endpoints = [
        'get' => Endpoint::CHANNEL,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Channel::class;
}
