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
use Discord\Parts\Channel\Reaction;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

/**
 * Contains reactions on a message.
 *
 * @see Reaction
 * @see \Discord\Parts\Channel\Message
 *
 * @since 5.1.0
 *
 * @method Reaction|null get(string $discrim, $key)
 * @method Reaction|null pull(string|int $key, $default = null)
 * @method Reaction|null first()
 * @method Reaction|null last()
 * @method Reaction|null find(callable $callback)
 */
class ReactionRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'get' => Endpoint::MESSAGE_REACTION_EMOJI,
        'delete' => Endpoint::MESSAGE_REACTION_EMOJI,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Reaction::class;

    /**
     * Delete all reactions for emoji.
     *
     * {@inheritDoc}
     *
     * @param Reaction|string $part The Reaction part or unicode emoji to delete.
     *
     * @return ExtendedPromiseInterface<Reaction>
     *
     * @since 10.0.0
     */
    public function delete($part, ?string $reason = null): ExtendedPromiseInterface
    {
        // Deal with unicode emoji
        if (is_string($part) && ! is_numeric($part)) {
            $part = $this->create([$this->discrim => $part, 'emoji' => (object) ['id' => null, 'name' => $part]], true);
        }

        return parent::delete($part, $reason);
    }
}
