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

use Discord\Parts\WebSockets\ApplicationAuthorized as ApplicationAuthorizedPart;
use Discord\WebSockets\Event;

/**
 * Received as a webhook event, through {@see \Discord\WebhookEvents\WebhookEventReceiver}.
 *
 * @link https://docs.discord.com/developers/events/webhook-events#application-authorized
 *
 * @see \Discord\Parts\WebSockets\ApplicationAuthorized
 *
 * @since 10.59.0
 */
class ApplicationAuthorized extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        $this->cacheUser($data->user);

        return $this->factory->part(ApplicationAuthorizedPart::class, (array) $data, true);
    }
}
