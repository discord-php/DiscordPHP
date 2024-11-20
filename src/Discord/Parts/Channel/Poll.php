<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\Channel\Poll\PollAnswer;
use Discord\Parts\Channel\Poll\PollMedia;
use Discord\Parts\Channel\Poll\PollResults;
use Discord\Parts\Part;
use Discord\Repository\Channel\PollAnswerRepository;
use React\Promise\PromiseInterface;

/**
 * A message poll.
 *
 * @link https://discord.com/developers/docs/resources/poll#poll-object
 *
 * @since 10.0.0
 *
 * @property PollMedia              $question            The question of the poll. Only text is supported.
 * @property PollAnswerRepository   $answers             Each of the answers available in the poll.
 * @property Carbon                 $expiry	             The time when the poll ends.
 * @property bool                   $allow_multiselect   Whether a user can select multiple answers.
 * @property int                    $layout_type         The layout type of the poll.
 * @property PollResults|null       $results             The results of the poll.
 *
 * @property string                 $channel_id          The ID of the channel the poll is in.
 * @property string                 $message_id          The ID of the message the poll is in.
 */
class Poll extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'question',
        'expiry',
        'allow_multiselect',
        'layout_type',
        'results',

        // events
        'channel_id',
        'message_id',

        // repositories
        'answers',
    ];

    /**
     * {@inheritdoc}
     */
    protected $repositories = [
        'answers' => PollAnswerRepository::class,
    ];

    /**
     * Sets the answers attribute.
     *
     * @param array $answers
     */
    protected function setAnswersAttribute(array $answers): void
    {
        foreach ($answers as $answer) {
            /** @var ?PollAnswer */
            if ($part = $this->answers->offsetGet($answer->answer_id)) {
                $part->fill($answer);
            } else {
                /** @var PollAnswer */
                $part = $this->answers->create($answer);
            }

            $this->answers->pushItem($part);
        }

        $this->attributes['answers'] = $answers;
    }

    /**
     * Returns the question attribute.
     *
     * @return PollMedia
     */
    protected function getQuestionAttribute(): PollMedia
    {
        return $this->factory->part(PollMedia::class, (array) $this->attributes['question'], true);
    }

    /**
     * Return the expiry attribute.
     *
     * @return Carbon
     *
     * @throws \Exception
     */
    protected function getExpiryAttribute(): Carbon
    {
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
     * Expire the poll.
     *
     * @link https://discord.com/developers/docs/resources/poll#end-poll
     *
     * @return PromiseInterface<Message>
     */
    public function expire(): PromiseInterface
    {
        return $this->http->post(Endpoint::bind(Endpoint::MESSAGE_POLL_EXPIRE, $this->channel_id, $this->message_id))
            ->then(function ($response) {
                return $this->factory->create(Message::class, (array) $response, true);
            });
    }
}
