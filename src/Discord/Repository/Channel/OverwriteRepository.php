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
use Discord\Parts\Channel\Overwrite;
use Discord\Repository\AbstractRepository;

/**
 * Contains permission overwrites for a channel.
 *
 * @see Overwrite
 * @see \Discord\Parts\Channel\Channel
 *
 * @since 4.0.0
 *
 * @method Overwrite|null get(string $discrim, $key)
 * @method Overwrite|null pull(string|int $key, $default = null)
 * @method Overwrite|null first()
 * @method Overwrite|null last()
 * @method Overwrite|null find(callable $callback)
 */
class OverwriteRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'delete' => Endpoint::CHANNEL_PERMISSIONS,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Overwrite::class;
}
