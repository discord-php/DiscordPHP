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

/**
 * A Label is a top-level layout component. Labels wrap modal components with text as a label and optional description.
 *
 * The description may display above or below the component depending on the platform.
 *
 * @link https://discord.com/developers/docs/components/reference#label
 *
 * @since 10.19.0
 *
 * @property int                             $type        18 for a label.
 * @property string                          $label       The label text; max 45 characters.
 * @property ?string|null                    $description An optional description text for the label; max 100 characters.
 * @property FileUpload|SelectMenu|TextInput $component   The component within the label.
 */
class Label extends Layout
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
        return $this->attributePartHelper('component', Component::TYPES[$this->attributes['component']->type ?? 0]);
    }
}
