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

/**
 * A Label is a top-level component.
 *
 * @link https://discord.com/developers/docs/components/reference#label
 *
 * @todo Update to match Discord's documentation upon public release.
 * @todo Update Label class to extend the relevant base class.
 *
 * @since 10.19.0
 *
 * @property int                  $type        18 for label component.
 * @property string               $label       The text for the label.
 * @property string|null          $description Optional description for the label.
 * @property SelectMenu|TextInput $component   The component associated with the label.
 */
class Label extends Component
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'type',
        'label',
        'description',
        'component',
    ];

    public function getComponentAttribute(): Component
    {
        return $this->createOf(Component::TYPES[$this->attributes['component']->type ?? 0], $this->attributes['component']);
    }
}
