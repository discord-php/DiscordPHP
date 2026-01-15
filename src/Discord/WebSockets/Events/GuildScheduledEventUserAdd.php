<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\ScheduledEventUser;
use Discord\Parts\User\User;
use Discord\WebSockets\Event;
use Discord\WebSockets\Events\Data\ScheduledEventUserData;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-scheduled-event-user-add
 *
 * @since 10.46.0 Added ScheduledEventUserData part
 * @since 7.0.0
 */
class GuildScheduledEventUserAdd extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        /** @var ScheduledEventUserData */
        $scheduledEventUserDataPart = $this->factory->part(ScheduledEventUserData::class, (array) $data, true);

        $userData = [
            'guild_scheduled_event_id' => $scheduledEventUserDataPart->guild_scheduled_event_id,
            'user_id' => $scheduledEventUserDataPart->user_id,
            'guild_id' => $scheduledEventUserDataPart->guild_id,
            'guild_scheduled_event_exception_id' => $scheduledEventUserDataPart->guild_scheduled_event_exception_id,
            // Reconstructed or cached
            'user' => $scheduledEventUserDataPart->user ?? $this->factory->part(User::class, ['id' => $scheduledEventUserDataPart->user_id], true),
            'member' => $scheduledEventUserDataPart->member,
        ];

        /** @var ScheduledEventUser */
        $scheduledEventUserPart = $this->factory->part(ScheduledEventUser::class, (array) $userData, true);

        return $scheduledEventUserPart;
    }
}
