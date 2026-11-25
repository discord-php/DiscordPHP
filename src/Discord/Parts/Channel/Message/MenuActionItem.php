<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel\Message;

use Discord\Parts\Guild\Emoji;

/**
 * A received action inside an overflow menu group.
 *
 * @property int         $type        Component type 26.
 * @property int|null    $id          Optional numeric component ID, not a custom ID.
 * @property string|null $custom_id   Developer-defined ID for Default and Danger actions.
 * @property bool|null   $disabled    Whether the action is disabled; absent defaults to false.
 * @property string      $label       User-facing action text.
 * @property int         $style       Default (1), Danger (2), or Link (3).
 * @property string|null $description Optional user-facing description.
 * @property string|null $url         Link destination, when present.
 * @property Emoji|null  $emoji       Partial emoji data, when present.
 */
class MenuActionItem extends Interactive
{
    protected $fillable = ['type', 'id', 'custom_id', 'disabled', 'label', 'style', 'description', 'url', 'emoji'];

    /**
     * Hydrates the optional partial emoji from the message payload.
     *
     * @return Emoji|null
     */
    protected function getEmojiAttribute(): ?Emoji
    {
        return $this->attributePartHelper('emoji', Emoji::class);
    }
}
