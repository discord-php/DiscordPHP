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
 * @link https://discord.com/developers/docs/topics/gateway-events#auto-moderation-rule-update
 *
 * @since 7.1.0
 */
class AutoModerationRuleUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $rulePart = $oldRule = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Rule */
            if ($oldRule = yield $guild->auto_moderation_rules->cacheGet($data->id)) {
                // Swap
                $rulePart = $oldRule;
                $oldRule = clone $oldRule;

                $rulePart->fill((array) $data);
            }
        }

        if ($rulePart === null) {
            /** @var Rule */
            $rulePart = $this->factory->part(Rule::class, (array) $data, true);
        }

        if (isset($guild)) {
            $guild->auto_moderation_rules->set($data->id, $rulePart);
        }

        return [$rulePart, $oldRule];
    }
}
