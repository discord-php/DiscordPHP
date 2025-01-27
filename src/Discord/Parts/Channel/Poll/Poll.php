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

use Discord\Parts\Part;

use function Discord\poly_strlen;

/**
 * A poll that can be attached to a message.
 *
 * @link https://discord.com/developers/docs/resources/poll#poll-create-request-object-poll-create-request-object-structure
 *
 * @since 10.0.0
 *
 * @property PollMedia         $question            The question of the poll. Only text is supported.
 * @property PollAnswer[]      $answers             Each of the answers available in the poll, up to 10.
 * @property int               $duration            Number of hours the poll should be open for, up to 7 days.
 * @property bool              $allow_multiselect   Whether a user can select multiple answers.
 * @property int|null          $layout_type?	    The layout type of the poll. Defaults to... DEFAULT!
 */
class Poll extends Part
{
    public const LAYOUT_DEFAULT = 1;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'question',
        'answers',
        'duration',
        'allow_multiselect',
        'layout_type',
    ];

    /**
     * Set the question attribute.
     *
     * @param PollMedia|string $question The question of the poll.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setQuestion(PollMedia|string $question): self
    {
        $question = $question instanceof PollMedia
            ? $question
            : new PollMedia($this->discord, [
                'text' => $question,
            ]);

        if (poly_strlen($question->text) > 300) {
            throw new \LengthException('Question must be maximum 300 characters.');
        }

        $this->attributes['question'] = $question;

        return $this;
    }

    /**
     * Set the answers attribute.
     *
     * @param PollAnswer[] $answers Each of the answers available in the poll.
     *
     * @return $this
     */
    public function setAnswers(array $answers): self
    {
        foreach ($answers as $answer) {
            $this->addAnswer($answer);
        }

        return $this;
    }

    /**
     * Add an answer to the poll.
     */
    public function addAnswer(PollAnswer|PollMedia|array|string $answer): self
    {
        if (count($this->answers ?? []) >= 10) {
            throw new \OutOfRangeException('Polls can only have up to 10 answers.');
        }

        if ($answer instanceof PollAnswer) {
            $this->attributes['answers'][] = $answer;

            return $this;
        }

        if (! $answer instanceof PollMedia) {
            $text = is_string($answer)
                ? $answer
                : $answer['text'];

            $emoji = $answer['emoji'] ?? null;

            $answer = (new PollMedia($this->discord))
                ->setText($text)
                ->setEmoji($emoji);
        }

        if (poly_strlen($answer->text) > 55) {
            throw new \LengthException('Answer must be maximum 55 characters.');
        }

        $this->attributes['answers'][] = new PollAnswer($this->discord, [
            'poll_media' => $answer,
        ]);

        return $this;
    }

    /**
     * Set the duration of the poll.
     *
     * @param int $duration Number of hours the poll should be open for, up to 32 days. Defaults to 24
     *
     * @throws \OutOfRangeException
     *
     * @return $this
     */
    public function setDuration(int $duration): self
    {
        if ($duration < 1 || $duration > 32 * 24) {
            throw new \OutOfRangeException('Duration must be between 1 and 32 days.');
        }

        $this->attributes['duration'] = $duration;

        return $this;
    }

    /**
     * Determine whether a user can select multiple answers.
     *
     * @param bool $multiselect Whether a user can select multiple answers.
     *
     * @return $this
     */
    public function setAllowMultiselect(bool $multiselect): self
    {
        $this->attributes['allow_multiselect'] = $multiselect;

        return $this;
    }

    /**
     * Set the layout type of the poll.
     *
     * @param int $type The layout type of the poll.
     *
     * @return $this
     */
    protected function setLayoutType(int $type): self
    {
        $this->attributes['layout_type'] = $type;

        return $this;
    }
}
