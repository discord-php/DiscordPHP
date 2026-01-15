<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Http\Endpoint;
use Discord\Parts\OAuth\ActivityInstance;

/**
 * Contains activity instances of an application.
 *
 * @see ActivityInstance
 * @see \Discord\Parts\User\Client
 *
 * @since 10.17.0
 */
class ActivityInstanceRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'get' => Endpoint::APPLICATION_ACTIVITY_INSTANCE,
    ];

    /**
     * @inheritDoc
     */
    protected $class = ActivityInstance::class;

    /**
     * @inheritDoc
     */
    public function __construct($discord, array $vars = [])
    {
        $vars['application_id'] = $discord->application->id;

        parent::__construct($discord, $vars);
    }
}
