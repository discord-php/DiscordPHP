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
use Discord\Parts\Channel\Channel;
use Discord\Repository\AbstractRepository;

/**
 * Contains channels that belong to guilds.
 *
 * @see \Discord\Parts\Channel\Channel
 * @see \Discord\Parts\Guild\Guild
 */
class ChannelRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_CHANNELS,
        'get' => Endpoint::CHANNEL,
        'create' => Endpoint::GUILD_CHANNELS,
        'update' => Endpoint::CHANNEL,
        'delete' => Endpoint::CHANNEL,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Channel::class;
}
