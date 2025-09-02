<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Embed;

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;

/**
 * @property      ?string|null                  $title                                       The title of the embed.
 * @property-read ?string|null                  $type                                        The type of the embed (always "rich" for webhook embeds).
 * @property      ?string|null                  $description                                 A description of the embed.
 * @property      ?string|null                  $url                                         The URL of the embed.
 * @property      ?Carbon|null                  $timestamp                                   A timestamp of the embed.
 * @property      ?int|null                     $color                                       The color of the embed.
 * @property      ?Footer|null                  $footer                                      The footer of the embed.
 * @property      ?Image|null                   $image                                       The image of the embed.
 * @property      ?Thumbnail|null               $thumbnail                                   The thumbnail of the embed.
 * @property-read ?Video|null                   $video                                       The video of the embed.
 * @property-read ?Provider|null                $provider                                    The provider of the embed.
 * @property      ?Author|null                  $author                                      The author of the embed.
 * @property      ExCollectionInterface|Field[] $fields                                      A collection of embed fields (max of 25).
 * @property-read array                         $poll_fields                                 A collection of poll fields.
 * @property      string|null                   $poll_fields['poll_question_text']           Question text from the original poll
 * @property      int|null                      $poll_fields['victor_answer_votes']          Number of votes for the answer(s) with the most votes
 * @property      int|null                      $poll_fields['total_votes']                  Total number of votes in the poll
 * @property      ?string|null                  $poll_fields['victor_answer_id']             ID for the winning answer (optional)
 * @property      ?string|null                  $poll_fields['victor_answer_text']           Text for the winning answer (optional)
 * @property      ?string|null                  $poll_fields['victor_answer_emoji_id']       ID for an emoji associated with the winning answer (optional)
 * @property      ?string|null                  $poll_fields['victor_answer_emoji_name']     Name of an emoji associated with the winning answer (optional)
 * @property      ?bool|null                    $poll_fields['victor_answer_emoji_animated'] If an emoji associated with the winning answer is animated (optional)
 */
class EmbedPollResult extends Embed
{
    public const TYPE = self::TYPE_POLL_RESULT;

    protected function getPollFieldsAttribute(): array
    {
        $fields = [];

        if (! array_key_exists('fields', $this->attributes)) {
            return $fields;
        }

        $fields = array_filter(
            $this->attributes['fields'] ?? [],
            fn($key) => !in_array($key, ['name', 'value', 'inline']),
            ARRAY_FILTER_USE_KEY
        );

        return $fields;
    }
}
