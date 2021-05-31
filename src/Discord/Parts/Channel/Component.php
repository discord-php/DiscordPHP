<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Component;

use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;

/**
 * A message which is posted to a Discord text channel.
 *
 * @property int                            $type                   The type of component.
 * @property int|null                       $style                  One of button styles
 * @property string|null                    $label                  Text that appears on the button, max 80 characters
 * @property Emoji|null                     $emoji                  Parital emoji, name, id, and animated
 * @property string|null                    $custom_id              A developer-defined identifier for the button, max 100 characters	Buttons
 * @property string|null                    $url                  	A url for link-style buttons Buttons
 * @property bool|null                      $disabled               whether the button is disabled, default false	Buttons
 
 */
class Component extends Part
{
    /**
     * Gets the partial emoji attribute.
     *
     * @return Emoji
     * @throws \Exception
     */
    protected function getEmojiAttribute(): ?Part
    {
        if (isset($this->attributes['emoji'])) {
            return $this->factory->create(Emoji::class, $this->attributes['emoji'], true);
        }

        return null;
    }
}
