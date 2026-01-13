J<?php

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
 * Represents the status of an invite target users job.
 * 
 * @link https://discord.com/developers/docs/resources/invite#get-target-users-job-status
 *
 * @since 10.46.0
 * 
 * @property int $status
 * @property int $total_users
 * @property int $processed_users
 * @property Carbon $created_at
 * @property ?Carbon $completed_at
 * @property ?string $error_message
 */

class InviteJobStatus extends Part
{
    /** The default value. */
    public const UNSPECIFIED = 0;
    /** The job is still being processed. */
    public const PROCESSING = 1;
    /** The job has been completed successfully. */
    public const COMPLETED = 2;
    /** The job has failed, see `error_message` field for more details. */
    public const FAILED = 3;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'status',
        'total_users',
        'processed_users',
        'created_at',
        'completed_at',
        'error_message',
    ];

    /**
     * Returns the created at attribute.
     *
     * @return Carbon The time that the invite was created.
     *
     * @throws \Exception
     */
    protected function getCreatedAtAttribute(): Carbon
    {
        return $this->attributeCarbonHelper('created_at');
    }

    /**
     * Returns the completed at attribute.
     *
     * @return Carbon|null The time that the invite was completed.
     *
     * @throws \Exception
     */
    protected function getCompletedAtAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('completed_at');
    }
}
}