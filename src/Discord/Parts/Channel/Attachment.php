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

use Carbon\Carbon;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\OAuth\Application;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * A message attachment.
 *
 * @link https://discord.com/developers/docs/resources/message#attachment-object
 *
 * @since 7.0.0
 *
 * @property string            $id                Attachment ID.
 * @property string            $filename          Name of file attached.
 * @property string|null       $title             The title of the file
 * @property string|null       $description       Description for the file.
 * @property string|null       $content_type      The attachment's media type.
 * @property int               $size              Size of file in bytes.
 * @property string            $url               Source url of file.
 * @property string            $proxy_url         A proxied url of file.
 * @property ?int|null         $height            Height of file (if image).
 * @property ?int|null         $width             Width of file (if image).
 * @property bool|null         $ephemeral         Whether this attachment is ephemeral.
 * @property float|null        $duration_secs     The duration of the audio file (currently for voice messages).
 * @property string|null       $waveform          Base64 encoded bytearray representing a sampled waveform (currently for voice messages).
 * @property int|null          $flags             Attachment flags combined as a bitfield.
 * @property ?User[]|null      $clip_participants Array of user objects. For Clips, array of users who were in the stream.
 * @property ?Carbon|null      $clip_created_at   For Clips, when the clip was created.
 * @property ?Application|null $application       For Clips, the application in the stream, if recognized.
 */
class Attachment extends Part
{
    /** This attachment is a Clip from a stream. */
    public const FLAG_IS_CLIP = 1 << 0;
    /** This attachment is the thumbnail of a thread in a media channel, displayed in the grid but not on the message. */
    public const FLAG_IS_THUMBNAIL = 1 << 1;
    /** This attachment has been edited using the remix feature on mobile (deprecated). */
    public const FLAG_IS_REMIX = 1 << 2;
    /** This attachment was marked as a spoiler and is blurred until clicked. */
    public const FLAG_IS_SPOILER = 1 << 3;
    /** This attachment is an animated image. */
    public const FLAG_IS_ANIMATED = 1 << 5;

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
        'clip_participants',
        'clip_created_at',
        'application',
    ];

    /**
     * Returns the clip participants attribute.
     *
     * @return ?ExCollectionInterface<User>
     */
    public function getClipParticipantsAttribute(): ?ExCollectionInterface
    {
        return $this->attributeCollectionHelper('clip_participants', User::class);
    }

    /**
     * Returns the clip created at attribute.
     *
     * @return ?Carbon
     */
    public function getClipCreatedAtAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('application');
    }

    /**
     * Returns the application attribute.
     *
     * @return ?Application
     */
    public function getApplicationAttribute(): ?Application
    {
        return $this->attributePartHelper('application', Application::class);
    }
}
