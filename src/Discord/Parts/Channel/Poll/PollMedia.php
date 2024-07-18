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
use Discord\Parts\Guild\Emoji;

/**
 * The poll media object is a common object that backs both the question and answers.
 *
 * @link https://discord.com/developers/docs/resources/poll#poll-media-object
 *
 * @since 10.0.0
 *
 * @property string|null        $text   The text of the field. Text should always be non-null for both questions and answers, but please do not depend on that in the future. The maximum length of text is 300 for the question, and 55 for any answer.
 * @property Emoji|string|null  $emoji  The emoji of the field. When creating a poll answer with an emoji, one only needs to send either the id (custom emoji) or name (default emoji) as the only field.
 */
class PollMedia extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'text',
        'emoji',
    ];

    /**
     * Sets the text of the poll media.
     *
     * @param string|null $text Text of the button. Maximum 300 characters for the question, and 55 for any answer.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Sets the emoji of the poll media.
     *
     * @param Emoji|string|null $emoji Emoji to set. `null` to clear.
     *
     * @return $this
     */
    public function setEmoji($emoji): self
    {
        $this->emoji = (function () use ($emoji) {
            if ($emoji === null) {
                return null;
            }

            if ($emoji instanceof Emoji) {
                return [
                    'id' => $emoji->id,
                    'name' => $emoji->name,
                    'animated' => $emoji->animated,
                ];
            }

            $parts = explode(':', $emoji, 3);

            if (count($parts) < 3) {
                return [
                    'id' => null,
                    'name' => $emoji,
                    'animated' => false,
                ];
            }

            [$animated, $name, $id] = $parts;

            return [
                'id' => $id,
                'name' => $name,
                'animated' => $animated == 'a',
            ];
        })();

        return $this;
    }
}
