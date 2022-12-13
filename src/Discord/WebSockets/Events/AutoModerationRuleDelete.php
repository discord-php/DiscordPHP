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
 * @link https://discord.com/developers/docs/topics/gateway-events#auto-moderation-rule-delete
 *
 * @since 7.1.0
 */
class AutoModerationRuleDelete extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $rulePart = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Rule */
            if ($rulePart = yield $guild->auto_moderation_rules->cachePull($data->id)) {
                $rulePart->fill((array) $data);
                $rulePart->created = false;
            }
        }

        return $rulePart ?? $this->factory->part(Rule::class, (array) $data);
    }
}
