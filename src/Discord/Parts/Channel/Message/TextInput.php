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
 * Text Input is an interactive component that allows users to enter free-form text responses in modals. It supports both short, single-line inputs and longer, multi-line paragraph inputs.
 *
 * Text Inputs can only be used within modals and must be placed inside an Action Row.
 *
 * When defining a text input component, you can set attributes to customize the behavior and appearance of it. However, not all attributes will be returned in the text input interaction payload.
 *
 * @link https://discord.com/developers/docs/components/reference#text-input
 *
 * @since 10.11.0
 *
 * @property int         $type         4 for a text input.
 * @property string|null $id           Optional identifier for component.
 * @property string      $custom_id    Developer-defined identifier for the input; max 100 characters.
 * @property int         $style        The Text Input Style.
 * @property string      $label        Label for this component; max 45 characters.
 * @property int|null    $min_length   Minimum input length for a text input; min 0, max 4000.
 * @property int|null    $max_length   Maximum input length for a text input; min 1, max 4000.
 * @property bool|null   $required     Whether this component is required to be filled (defaults to true).
 * @property string|null $value        Pre-filled value for this component; max 4000 characters.
 * @property string|null $placeholder  Custom placeholder text if the input is empty; max 100 characters.
 */
class TextInput extends Interactive
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'custom_id',
        'style',
        'label',
        'min_length',
        'max_length',
        'required',
        'value',
        'placeholder',
    ];
}
