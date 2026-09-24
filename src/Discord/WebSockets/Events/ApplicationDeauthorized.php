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

use Discord\Parts\User\User;
use Discord\WebSockets\Event;

/**
 * Received as a webhook event, through {@see \Discord\WebhookEvents\WebhookEventReceiver}.
 *
 * For Social SDK apps, the user's OAuth2 tokens are no longer valid and their account has been unmerged.
 *
 * @link https://docs.discord.com/developers/events/webhook-events#application-deauthorized
 *
 * @since 10.59.0
 */
class ApplicationDeauthorized extends Event
{
    /**
     * @inheritDoc
     *
     * @return User The user who deauthorized the application.
     */
    public function handle($data)
    {
        $this->cacheUser($data->user);

        return $this->discord->users->get('id', $data->user->id)
            ?? $this->factory->part(User::class, (array) $data->user, true);
    }
}
