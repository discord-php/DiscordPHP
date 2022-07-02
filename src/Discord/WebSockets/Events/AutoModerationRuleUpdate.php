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
use Discord\Helpers\Deferred;
use Discord\Parts\Guild\AutoModeration\Rule;

/**
 * @see https://discord.com/developers/docs/topics/gateway#auto-moderation-rule-update
 */
class AutoModerationRuleUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $rulePart = $oldRule = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($oldRule = $guild->auto_moderation_rules->get('id', $data->id)) {
                // Swap
                $rulePart = $oldRule;
                $oldRule = clone $oldRule;

                $rulePart->fill((array) $data);
            }
        }

        if (! $rulePart) {
            /** @var Rule */
            $rulePart = $this->factory->create(Rule::class, $data, true);
            if ($guild = $rulePart->guild) {
                $guild->auto_moderation_rules->pushItem($rulePart);
            }
        }

        $deferred->resolve([$rulePart, $oldRule]);
    }
}
