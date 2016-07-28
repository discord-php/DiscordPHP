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

use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\Repository\AbstractRepository;

/**
 * Contains voice states for users in the voice channel.
 *
 * @see Discord\Parts\WebSockets\VoiceStateUpdate
 * @see Discord\Parts\Channel\Channel
 */
class VoiceMemberRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [];

    /**
     * {@inheritdoc}
     */
    protected $class = VoiceStateUpdate::class;
}
