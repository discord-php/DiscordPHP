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

use Discord\Parts\Lobby\Message;
use Discord\WebSockets\Event;

/**
 * Received as a webhook event, through {@see \Discord\WebhookEvents\WebhookEventReceiver}.
 *
 * @link https://docs.discord.com/developers/events/webhook-events#lobby-message-delete
 *
 * @see \Discord\Parts\Lobby\Message
 *
 * @since 10.59.0
 */
class LobbyMessageDelete extends Event
{
    /**
     * @inheritDoc
     *
     * @return Message The deleted message, with only its `id` and `lobby_id`.
     */
    public function handle($data)
    {
        return $this->factory->part(Message::class, (array) $data, false);
    }
}
