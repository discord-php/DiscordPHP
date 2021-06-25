<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild\AuditLog;

use Discord\Parts\Part;

/**
 * Represents an object of options for different audit log action types.
 * Not all options will be present. See the Discord developer docs for
 * more information: https://discord.com/developers/docs/resources/audit-log#audit-log-entry-object-optional-audit-entry-info.
 *
 * @see https://discord.com/developers/docs/resources/audit-log#audit-log-entry-object-optional-audit-entry-info
 *
 * @property string $delete_member_days
 * @property string $members_removed
 * @property string $channel_id
 * @property string $message_id
 * @property string $count
 * @property string $id
 * @property string $type
 * @property string $role_name
 */
class Options extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'delete_member_days',
        'members_removed',
        'channel_id',
        'message_id',
        'count',
        'id',
        'type',
        'role_name',
    ];
}
