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

use Discord\Helpers\ExCollectionInterface;

/**
 * A Section displays one to three text components alongside an accessory in a message.
 *
 * @link https://docs.discord.com/developers/components/reference#section
 *
 * @since 10.11.0
 *
 * @property int                                              $type       9 for section component.
 * @property int|null                                         $id         Optional identifier for component.
 * @property ExCollectionInterface<TextDisplay>|TextDisplay[] $components One to three text components.
 * @property Thumbnail|Button|OverflowMenu                    $accessory  A thumbnail, button, or overflow menu component.
 *
 * @phpstan-property ExCollectionInterface<TextDisplay> $components
 */
class Section extends Layout
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'type',
        'id',
        'components',
        'accessory',
    ];

    /** @return Thumbnail|Button|OverflowMenu */
    protected function getAccessoryAttribute(): Component
    {
        if (($this->attributes['accessory'] ?? null) instanceof Component) {
            return $this->attributes['accessory'];
        }

        $accessory = (array) ($this->attributes['accessory'] ?? []);

        return $this->attributePartHelper('accessory', Component::TYPES[$accessory['type'] ?? 0]);
    }
}
