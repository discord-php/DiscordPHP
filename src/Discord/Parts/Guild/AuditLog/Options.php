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
 * @property string $channel_id         Channel in which the entities were targeted.
 * @property string $count              Number of entities that were targeted.
 * @property string $delete_member_days Number of days after which inactive members were kicked.
 * @property string $id                 Id of the overwritten entity.
 * @property string $members_removed    Number of members removed by the prune.
 * @property string $message_id         Id of the message that was targeted.
 * @property string $role_name          Name of the role if type is "0" (not present if type is "1").
 * @property string $type               Type of overwritten entity - "0" for "role" or "1" for "member".
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
