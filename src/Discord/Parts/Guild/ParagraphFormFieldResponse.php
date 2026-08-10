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
 * @property ?string|null          $placeholder Placeholder text shown in empty input.
 * @property ?bool|string|int|null $response    Applicant's text response.
 */
class ParagraphFormFieldResponse extends FormFieldResponse
{
    /** @inheritDoc */
    protected $fillable = [
        'field_type',
        'label',
        'description',
        'required',
        'placeholder',
        'response',
    ];
}
