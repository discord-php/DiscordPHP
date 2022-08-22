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
use Discord\Parts\Channel\StageInstance;
use Discord\Repository\AbstractRepository;

/**
 * Contains a live stage instances channel.
 *
 * @see StageInstance
 * @see \Discord\Parts\Guild\Guild
 *
 * @method StageInstance|null get(string $discrim, $key)
 * @method StageInstance|null pull(string|int $key, $default = null)
 * @method StageInstance|null first()
 * @method StageInstance|null last()
 * @method StageInstance|null find()
 */
class StageInstanceRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $discrim = 'channel_id';

    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'get' => Endpoint::STAGE_INSTANCE,
        'create' => Endpoint::STAGE_INSTANCES,
        'update' => Endpoint::STAGE_INSTANCE,
        'delete' => Endpoint::STAGE_INSTANCE,
    ];

    /**
     * @inheritdoc
     */
    protected $class = StageInstance::class;
}
