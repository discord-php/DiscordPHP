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
 * An action in an overflow menu group.
 *
 * @property int         $type
 * @property int|null    $id
 * @property string      $label
 * @property int         $style
 * @property string|null $description
 * @property string|null $url
 * @property Emoji|null  $emoji
 */
class MenuActionItem extends Component
{
    protected $fillable = ['type', 'id', 'label', 'style', 'description', 'url', 'emoji'];

    protected function getEmojiAttribute(): ?Emoji
    {
        return $this->attributePartHelper('emoji', Emoji::class);
    }
}
