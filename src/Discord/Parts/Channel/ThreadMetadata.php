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
use Discord\Parts\Part;

/**
 * The thread metadata object contains a number of thread-specific channel fields that are not needed by other channel types.
 *
 * @link https://discord.com/developers/docs/resources/channel#thread-metadata-object
 *
 * @since 10.22.0
 *
 * @property bool         $archived              Whether the thread is archived.
 * @property int          $auto_archive_duration The thread will stop showing in the channel list after auto_archive_duration minutes of inactivity.
 * @property string       $archive_timestamp     Timestamp when the thread's archive status was last changed (ISO8601).
 * @property bool         $locked                Whether the thread is locked; only users with MANAGE_THREADS can unarchive it.
 * @property ?bool|null   $invitable             Whether non-moderators can add other non-moderators to a thread; only available on private threads.
 * @property ?Carbon|null $create_timestamp      Timestamp when the thread was created (ISO8601); only populated for threads created after 2022-01-09.
 */
class ThreadMetadata extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'archived',
        'auto_archive_duration',
        'archive_timestamp',
        'locked',
        'invitable',
        'create_timestamp',
    ];

    protected function getCreateTimestampAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('create_timestamp');
    }
}
