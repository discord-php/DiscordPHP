<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\Sound;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * Contains sounds of a guild.
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
        'all' => Endpoint::GUILD_SOUNDBOARD_SOUNDS,
        'get' => Endpoint::GUILD_SOUNDBOARD_SOUND,
        'create' => Endpoint::GUILD_SOUNDBOARD_SOUNDS,
        'delete' => Endpoint::GUILD_SOUNDBOARD_SOUND,
        'update' => Endpoint::GUILD_SOUNDBOARD_SOUND,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Sound::class;

    /**
     * @param object $response
     *
     * @return PromiseInterface<static>
     */
    protected function cacheFreshen($response): PromiseInterface
    {
        foreach ($response as $value) foreach ($value as $value) {
            $value = array_merge($this->vars, (array) $value);
            $part = $this->factory->create($this->class, $value, true);
            $items[$part->{$this->discrim}] = $part;
        }

        if (empty($items)) {
            return resolve($this);
        }

        return $this->cache->setMultiple($items)->then(fn ($success) => $this);
    }
}
