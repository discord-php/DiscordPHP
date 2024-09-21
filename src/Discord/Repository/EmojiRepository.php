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

use Discord\Http\Endpoint;
use Discord\Parts\Guild\Emoji;
use Discord\Repository\AbstractRepository;

/**
 * Contains emojis of an application.
 *
 * @see Emoji
 * @see \Discord\Parts\User\Client
 *
 * @since 4.0.2
 *
 * @method Emoji|null get(string $discrim, $key)
 * @method Emoji|null pull(string|int $key, $default = null)
 * @method Emoji|null first()
 * @method Emoji|null last()
 * @method Emoji|null find(callable $callback)
 */
class EmojiRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::APPLICATION_EMOJIS,
        'get' => Endpoint::APPLICATION_EMOJI,
        'create' => Endpoint::APPLICATION_EMOJIS,
        'delete' => Endpoint::APPLICATION_EMOJI,
        'update' => Endpoint::APPLICATION_EMOJI,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Emoji::class;
}
