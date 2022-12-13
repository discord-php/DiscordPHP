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
 * Not all options will be present. See the Discord developer docs for more
 * information.
 *
 * @link https://discord.com/developers/docs/resources/audit-log#audit-log-entry-object-optional-audit-entry-info
 *
 * @since 5.1.0
 *
 * @property string $application_id                    ID of the app whose permissions were targeted.
 * @property string $auto_moderation_rule_name         Name of the Auto Moderation rule that was triggered.
 * @property string $auto_moderation_rule_trigger_type Trigger type of the Auto Moderation rule that was triggered.
 * @property string $channel_id                        Channel in which the entities were targeted.
 * @property string $count                             Number of entities that were targeted.
 * @property string $delete_member_days                Number of days after which inactive members were kicked.
 * @property string $id                                Id of the overwritten entity.
 * @property string $members_removed                   Number of members removed by the prune.
 * @property string $message_id                        Id of the message that was targeted.
 * @property string $role_name                         Name of the role if type is "0" (not present if type is "1").
 * @property string $type                              Type of overwritten entity - "0" for "role" or "1" for "member".
 */
class Options extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'application_id',
        'auto_moderation_rule_name',
        'auto_moderation_rule_trigger_type',
        'channel_id',
        'count',
        'delete_member_days',
        'id',
        'members_removed',
        'message_id',
        'role_name',
        'type',
    ];
}
