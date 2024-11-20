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
use Discord\Parts\Channel\StageInstance;
use Discord\Repository\AbstractRepository;

/**
 * Contains a live stage instances channel.
 *
 * @see StageInstance
 * @see \Discord\Parts\Channel\Channel
 *
 * @since 10.0.0 Moved from Guild to Channel
 * @since 7.0.0
 *
 * @method StageInstance|null get(string $discrim, $key)
 * @method StageInstance|null pull(string|int $key, $default = null)
 * @method StageInstance|null first()
 * @method StageInstance|null last()
 * @method StageInstance|null find(callable $callback)
 */
class StageInstanceRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'get' => Endpoint::STAGE_INSTANCE,
        'create' => Endpoint::STAGE_INSTANCES,
        'update' => Endpoint::STAGE_INSTANCE,
        'delete' => Endpoint::STAGE_INSTANCE,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = StageInstance::class;
}
