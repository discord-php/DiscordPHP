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

use Discord\Discord;
use Discord\Http\Endpoint;
use Discord\Parts\Guild\Emoji;

/**
 * Contains emojis of an application.
 *
 * @see Emoji
 * @see \Discord\Parts\User\Client
 *
 * @since 10.0.0
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

    /**
     * {@inheritDoc}
     */
    public function __construct(Discord $discord, array $vars = [])
    {
        $vars['application_id'] = $discord->application->id;

        parent::__construct($discord, $vars);
    }
}
