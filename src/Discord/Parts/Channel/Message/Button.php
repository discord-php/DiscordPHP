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

use Discord\Parts\Guild\Emoji;

/**
 * A Button is an interactive component that can only be used in messages.
 * It creates clickable elements that users can interact with, sending an interaction to your app when clicked.
 *
 * Buttons must be placed inside an Action Row or a Section's accessory field.
 *
 * @link https://discord.com/developers/docs/components/reference#button
 *
 * @since 10.11.0
 *
 * @property int         $type      2 for a button.
 * @property string|null $id        Optional identifier for component.
 * @property string      $style     A button style.
 * @property string|null $label     Text that appears on the button; max 80 characters.
 * @property Emoji|null  $emoji     name, id, and animated.
 * @property string      $custom_id Developer-defined identifier for the button; max 100 characters.
 * @property string|null $sku_id    Identifier for a purchasable SKU, only available when using premium-style buttons.
 * @property string|null $url       URL for link-style buttons.
 * @property bool|null   $disabled  Whether the button is disabled (defaults to false).
 */
class Button extends Interactive
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'style',
        'label',
        'emoji',
        'custom_id',
        'sku_id',
        'url',
        'disabled',
    ];

    /**
     * Gets the partial emoji attribute.
     *
     * @return Emoji|null
     */
    protected function getEmojiAttribute(): ?Emoji
    {
        if (! isset($this->attributes['emoji'])) {
            return null;
        }

        return $this->factory->part(Emoji::class, (array) $this->attributes['emoji'], true);
    }
}
