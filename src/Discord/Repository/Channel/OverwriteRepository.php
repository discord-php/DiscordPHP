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

use Discord\Parts\Channel\Overwrite;
use Discord\Repository\AbstractRepository;

/**
 * Contains permission overwrites for channels.
 *
 * @see Discord\Parts\Channel\Overwrite
 * @see Discord\Parts\Channel\Channel
 */
class OverwriteRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'delete' => 'channels/:channel_id/permissions/:id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $part = Overwrite::class;
}
