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
 * @see VoiceStateUpdate
 * @see \Discord\Parts\Channel\Channel
 *
 * @since 4.0.0
 *
 * @method VoiceStateUpdate|null get(string $discrim, $key)
 * @method VoiceStateUpdate|null pull(string|int $key, $default = null)
 * @method VoiceStateUpdate|null first()
 * @method VoiceStateUpdate|null last()
 * @method VoiceStateUpdate|null find(callable $callback)
 */
class VoiceMemberRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $discrim = 'user_id';

    /**
     * {@inheritDoc}
     */
    protected $endpoints = [];

    /**
     * {@inheritDoc}
     */
    protected $class = VoiceStateUpdate::class;
}
