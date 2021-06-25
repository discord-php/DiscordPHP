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
use Discord\Parts\Guild\Invite;
use Discord\Repository\AbstractRepository;

/**
 * Contains invites for channels.
 *
 * @see \Discord\Parts\Guild\Invite
 * @see \Discord\Parts\Channel\Channel
 */
class InviteRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::CHANNEL_INVITES,
        'get' => Endpoint::INVITE,
        'create' => Endpoint::CHANNEL_INVITES,
        'delete' => Endpoint::INVITE,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Invite::class;
}
