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

use Discord\Parts\Channel\GameDirectMessage;
use Discord\WebSockets\Event;

/**
 * Received as a webhook event, through {@see \Discord\WebhookEvents\WebhookEventReceiver}.
 *
 * @link https://docs.discord.com/developers/events/webhook-events#game-direct-message-delete
 *
 * @see \Discord\Parts\Channel\GameDirectMessage
 *
 * @since 10.59.0
 */
class GameDirectMessageDelete extends Event
{
    /**
     * @inheritDoc
     *
     * @return GameDirectMessage The deleted message, as it was.
     */
    public function handle($data)
    {
        return $this->factory->part(GameDirectMessage::class, (array) $data, false);
    }
}
