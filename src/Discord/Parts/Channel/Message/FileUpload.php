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
 * A File Upload is an interactive component that allows users to upload files in modals.
 *
 * @link https://discord.com/developers/docs/components/reference#file-upload
 *
 * @since 10.21.0
 *
 * @property int        $type       19 for File Upload component.
 * @property string     $custom_id  Developer-defined identifier, max 100 characters.
 * @property ?int|null  $min_values Minimum number of files that must be uploaded (defaults to 1); min 0, max 10.
 * @property ?int|null  $max_values Maximum number of files that can be uploaded (defaults to 1); max 10.
 * @property ?bool|null $required   Whether the file upload is required to be filled in a modal (defaults to true).
 */
class FileUpload extends Interactive
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'custom_id',
        'min_values',
        'max_values',
        'required',
    ];
}
