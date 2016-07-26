<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Channel;

use Discord\Parts\Guild\Invite;
use Discord\Repository\AbstractRepository;

/**
 * Contains invites for channels.
 *
 * @see Discord\Parts\Guild\Invite
 * @see Discord\Parts\Channel\Channel
 */
class InviteRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all'    => 'channels/:channel_id/invites',
        'get'    => 'invites/:code',
        'create' => 'channels/:channel_id/invites',
        'delete' => 'invites/:code',
    ];

    /**
     * {@inheritdoc}
     */
    protected $part = Invite::class;
}
