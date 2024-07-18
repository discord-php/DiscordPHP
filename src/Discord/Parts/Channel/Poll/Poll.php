<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel\Poll;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\ExtendedPromiseInterface;

/**
 * A message poll.
 *
 * @link https://discord.com/developers/docs/resources/poll#poll-object
 *
 * @since 10.0.0
 *
 * @property PollMedia|string   $question           The question of the poll. Only text is supported.
 * @property PollAnswer[]       $answers            Each of the answers available in the poll.
 * @property Carbon|null        $expiry	            The time when the poll ends.
 * @property bool               $allow_multiselect  Whether a user can select multiple answers.
 * @property int                $layout_type        The layout type of the poll.
 * @property PollResults|null   $results            The results of the poll.

 * @property string             $channel_id         The ID of the channel the poll is in.
 * @property string             $message_id         The ID of the message the poll is in.
 */
class Poll extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'question',
        'answers',
        'expiry',
        'allow_multiselect',
        'layout_type',
        'results',
        'channel_id',
        'message_id',
    ];

    /**
     * Returns the question attribute.
     *
     * @return PollMedia|null
     */
    protected function getQuestionAttribute(): ?PollMedia
    {
        if (! isset($this->attributes['question'])) {
            return null;
        }

        return $this->factory->part(PollMedia::class, (array) $this->attributes['question'], true);
    }

    /**
     * Returns the answers attribute.
     *
     * @return PollAnswer[]
     */
    protected function getAnswersAttribute(): array
    {
        if (! isset($this->attributes['answers'])) {
            return [];
        }

        return array_map(function ($answer) {
            return $this->factory->part(PollAnswer::class, (array) $answer, true);
        }, $this->attributes['answers']);
    }

    /**
     * Return the expiry attribute.
     *
     * @return Carbon|null
     *
     * @throws \Exception
     */
    protected function getExpiryAttribute(): ?Carbon
    {
        if (! isset($this->attributes['expiry'])) {
            return null;
        }

        return Carbon::parse($this->attributes['expiry']);
    }

    /**
     * Returns the results attribute.
     *
     * @return PollResults|null
     */
    protected function getResultsAttribute(): ?PollResults
    {
        if (! isset($this->attributes['results'])) {
            return null;
        }

        return $this->factory->part(PollResults::class, (array) $this->attributes['results'], true);
    }

    /**
     * Returns the users that voted for the specified answer.
     *
     * @param int      $answerId The answer ID to get voters for.
     * @param int      $limit    The maximum number of users to return.
     * @param int|null $after    The user ID to get users after.
     *
     * @link https://discord.com/developers/docs/resources/poll#get-answer-voters
     *
     * @return ExtendedPromiseInterface<Collection|User[]>
     */
    public function getAnswerVoters(int $answerId, int $limit = 25, ?int $after = null): ExtendedPromiseInterface
    {
        $endpoint = Endpoint::bind(Endpoint::CHANNEL_POLL_ANSWERS, $this->channel_id, $this->message_id, $answerId);

        $endpoint->addQuery('limit', $limit);

        if ($after) {
            $endpoint->addQuery('after', $after);
        }

        return $this->http->get($endpoint)
            ->then(function ($response) {
                $users = Collection::for(User::class);

                foreach ($response as $user) {
                    if (! $part = $this->discord->users->get('id', $user->id)) {
                        $part = $this->discord->users->create($user, true);

                        $this->discord->users->pushItem($part);
                    }

                    $users->pushItem($part);
                }

                return $users;
            });
    }

    /**
     * End the poll.
     *
     * @link https://discord.com/developers/docs/resources/poll#end-poll
     *
     * @return ExtendedPromiseInterface
     */
    public function end(): ExtendedPromiseInterface
    {
        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_POLL_EXPIRE, $this->channel_id, $this->message_id));
    }
}
