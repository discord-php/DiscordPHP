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

namespace Discord\WebSockets\Events\Data;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\StageInstance;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Parts\Guild\Sound;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\Member;
use Discord\Parts\WebSockets\PresenceUpdate;
use Discord\Parts\WebSockets\VoiceStateUpdate;

/**
 * An available or unavailable guild with extra fields.
 *
 * @see \Discord\Websockets\Events\GuildCreate
 *
 * @link https://discord.com/developers/docs/events/gateway-events#guild-create-guild-create-extra-fields
 *
 * @since 10.38.2
 *
 * @property string             $joined_at              ISO8601 timestamp when this guild was joined.
 * @property bool               $large                  `True` if this is considered a large guild.
 * @property bool|null          $unavailable            `True` if this guild is unavailable due to an outage.
 * @property int                $member_count           Total number of members in this guild.
 * @property VoiceStateUpdate[] $voice_states           States of members currently in voice channels (partial voice state objects; lacks `guild_id`).
 * @property Member[]           $members                Array of guild member objects.
 * @property Channel[]          $channels               Array of channel objects.
 * @property Thread[]           $threads                All active threads in the guild that current user has permission to view.
 * @property PresenceUpdate[]   $presences              Presences of the members in the guild (will only include non-offline members if size > `large threshold`).
 * @property StageInstance[]    $stage_instances        Array of stage instance objects in the guild.
 * @property ScheduledEvent[]   $guild_scheduled_events Array of guild scheduled event objects.
 * @property Sound[]            $soundboard_sounds      Array of soundboard sound objects.
 */
class GuildCreateData extends Guild
{
}
