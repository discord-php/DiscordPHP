<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\WebSockets\Event;
use Discord\Parts\Guild\AutoModeration\Rule;
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#auto-moderation-rule-create
 *
 * @since 7.1.0
 */
class AutoModerationRuleCreate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var Rule */
        $rulePart = $this->factory->part(Rule::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            $guild->auto_moderation_rules->set($data->id, $rulePart);
        }

        return $rulePart;
    }
}
