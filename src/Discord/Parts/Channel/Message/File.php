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
 * A File is a top-level component that allows you to display an uploaded file as an attachment to the message and reference it in the component. Each file component can only display 1 attached file, but you can upload multiple files and add them to different file components within your payload.
 *
 * Files are only available in messages.
 *
 * @link https://discord.com/developers/docs/components/reference#text-display
 *
 * @since 10.11.0
 *
 * @property int               $type    13 for file component.
 * @property string|null       $id      Optional identifier for component.
 * @property UnfurledMediaItem $file    Unfurled media item, supports only attachment://<filename> syntax.
 * @property bool|null         $spoiler Whether the media should be a spoiler (blurred out). Defaults to false.
 */
class File extends Content
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'file',
        'spoiler',
    ];

    protected function getFileAttribute(): UnfurledMediaItem
    {
        return $this->createOf(UnfurledMediaItem::class, $this->attributes['file'], true);
    }
}
