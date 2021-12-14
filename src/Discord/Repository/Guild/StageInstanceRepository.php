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
 * @method StageInstance|null get(string $discrim, $key)  Gets an item from the collection.
 * @method StageInstance|null first()                     Returns the first element of the collection.
 * @method StageInstance|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method StageInstance|null find(callable $callback)    Runs a filter callback over the repository.
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
