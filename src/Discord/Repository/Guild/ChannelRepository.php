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
}
