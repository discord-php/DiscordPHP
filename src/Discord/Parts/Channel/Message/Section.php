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

namespace Discord\Parts\Channel\Message;

use Discord\Helpers\Collection;

/**
 * A Separator is a top-level layout component that adds vertical padding and visual division between other components.
 *
 * Separators are only available in messages.
 *
 * @link https://discord.com/developers/docs/components/reference#section
 *
 * @since 10.11.0
 *
 * @property int                                 $type        9 for section component.
 * @property int|null                            $id          Optional identifier for component.
 * @property ExCollectionInterface|TextDisplay[] $components  One to three text components.
 * @property Thumbnail|Button                    $accessory   A thumbnail or a button component, with a future possibility of adding more compatible components.
 */
class Section extends Layout
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'components',
        'accessory',
    ];

    /** @return Thumbnail|Button */
    protected function getAccessoryAttribute(): Component
    {
        return $this->createOf(Component::TYPES[$this->attributes['accessory']['type'] ?? 0], $this->attributes['accessory']);
    }
}
