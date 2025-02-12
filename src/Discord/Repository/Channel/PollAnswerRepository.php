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
use Discord\Parts\Channel\Poll\PollAnswer;
use Discord\Repository\AbstractRepository;

/**
 * Contains poll answers on a poll in a message.
 *
 * @see PollAnswer
 * @see \Discord\Parts\Channel\Poll
 * @see \Discord\Parts\Channel\Message
 *
 * @since 10.0.0
 *
 * @method PollAnswer|null get(string $discrim, $key)
 * @method PollAnswer|null pull(string|int $key, $default = null)
 * @method PollAnswer|null first()
 * @method PollAnswer|null last()
 * @method PollAnswer|null find(callable $callback)
 */
class PollAnswerRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'get' => Endpoint::MESSAGE_POLL_ANSWER,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = PollAnswer::class;
}
