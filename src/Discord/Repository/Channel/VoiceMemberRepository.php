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

use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\Repository\AbstractRepository;

/**
 * Contains voice states for users in the voice channel.
 *
 * @see \Discord\Parts\WebSockets\VoiceStateUpdate
 * @see \Discord\Parts\Channel\Channel
 */
class VoiceMemberRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $discrim = 'user_id';

    /**
     * @inheritdoc
     */
    protected $endpoints = [];

    /**
     * @inheritdoc
     */
    protected $class = VoiceStateUpdate::class;
}
