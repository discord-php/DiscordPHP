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
use Discord\Parts\Guild\Sound;
use Discord\Repository\AbstractRepository;

/**
 * Contains sounds of an application.
 *
 * @see Sound
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 10.0.0
 *
 * @method Sound|null get(string $discrim, $key)
 * @method Sound|null pull(string|int $key, $default = null)
 * @method Sound|null first()
 * @method Sound|null last()
 * @method Sound|null find(callable $callback)
 */
class SoundRepository extends AbstractRepository
{
    /**
     * The discriminator.
     *
     * @var string Discriminator.
     */
    protected $discrim = 'sound_id';

    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::SOUNDBOARD_DEFAULT_SOUNDS,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Sound::class;
}
