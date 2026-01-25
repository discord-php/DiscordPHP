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

use Discord\Parts\Guild\Guild;
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#voice-state-update
 *
 * @see \Discord\Parts\WebSockets\VoiceStateUpdate
 *
 * @since 2.1.3
 */
class VoiceStateUpdate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        $oldVoiceState = null;
        /** @var VoiceStateUpdatePart */
        $statePart = $this->factory->part(VoiceStateUpdatePart::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var Guild $guild */
            if (isset($data->member)) {
                $this->cacheMember($guild->members, (array) $data->member);
                $this->cacheUser($data->member->user);
            }

            $oldVoiceState = yield $guild->voice_states->cacheGet($data->user_id);

            yield $guild->voice_states->cache->set($data->user_id, $statePart);
        }

        return [$statePart, $oldVoiceState];
    }
}
