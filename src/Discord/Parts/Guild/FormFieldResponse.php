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

namespace Discord\Parts\Guild;

use Discord\Parts\Part;

/**
 * Represents a single form field response in a guild join request.
 * 
 * @link TODO
 *
 * @since 10.55.0
 *
 * @property string      $field_type  Field type (TERMS, TEXT_INPUT, PARAGRAPH, MULTIPLE_CHOICE)
 * @property string|null $label       Label shown above the field.
 * @property string|null $description Helper text shown below the label.
 * @property bool|null   $required    Whether the applicant must fill in the field.
 *
 * @property string[]|null         $values      Terms the applicant must acknowledge.
 * @property ?string|null          $placeholder Placeholder text shown in empty input.
 * @property string[]|null         $choices     Choices the applicant can select from.
 * @property ?bool|string|int|null $response    The applicant's response.
 */
class FormFieldResponse extends Part
{
    /** Field requiring the applicant to acknowledge a list of terms. */
    public const TYPE_TERMS = 'TERMS';
    /** Short text input field. */
    public const TYPE_TEXT_INPUT = 'TEXT_INPUT';
    /** Long-form text input field. */
    public const TYPE_PARAGRAPH = 'PARAGRAPH';
    /** Field where the applicant selects one of many options. */
    public const TYPE_MULTIPLE_CHOICE = 'MULTIPLE_CHOICE';

    /** @todo Add classes for each field type */
    public const TYPES = [
        0 => FormFieldResponse::class, // Fallback for unknown types
        FormFieldResponse::TYPE_TERMS => FormFieldResponse::class,
        FormFieldResponse::TYPE_TEXT_INPUT => FormFieldResponse::class,
        FormFieldResponse::TYPE_PARAGRAPH => FormFieldResponse::class,
        FormFieldResponse::TYPE_MULTIPLE_CHOICE => FormFieldResponse::class,
    ];

    /** @inheritDoc */
    protected $fillable = [
        'field_type',
        'label',
        'description',
        'required',
        'values',
        'placeholder',
        'response',
        'choices',
    ];
}
