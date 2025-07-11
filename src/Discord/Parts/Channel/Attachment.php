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

namespace Discord\Parts\Channel;

use Discord\Parts\Part;

/**
 * A message attachment.
 *
 * @link https://discord.com/developers/docs/resources/channel#attachment-object
 *
 * @since 7.0.0
 *
 * @property string      $id            Attachment ID.
 * @property string      $filename      Name of file attached.
 * @property string|null $title         The title of the file
 * @property string|null $description   Description for the file.
 * @property string|null $content_type  The attachment's media type.
 * @property int         $size          Size of file in bytes.
 * @property string      $url           Source url of file.
 * @property string      $proxy_url     A proxied url of file.
 * @property ?int|null   $height        Height of file (if image).
 * @property ?int|null   $width         Width of file (if image).
 * @property bool|null   $ephemeral     Whether this attachment is ephemeral.
 * @property float|null  $duration_secs The duration of the audio file (currently for voice messages).
 * @property string|null $waveform      Base64 encoded bytearray representing a sampled waveform (currently for voice messages).
 * @property int|null    $flags         Attachment flags combined as a bitfield.
 */
class Attachment extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'filename',
        'title',
        'description',
        'content_type',
        'size',
        'url',
        'proxy_url',
        'height',
        'width',
        'ephemeral',
        'duration_secs',
        'waveform',
        'flags',
    ];
}
