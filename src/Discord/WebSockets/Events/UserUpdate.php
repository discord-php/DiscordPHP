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

use Discord\WebSockets\Event;
use Discord\Parts\User\User;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#user-update
 *
 * @since 7.0.0
 */
class UserUpdate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        /** @var User */
        if ($oldUser = yield $this->discord->users->cacheGet($data->id)) {
            $userPart = clone $oldUser;
            $userPart->fill((array) $data);
        } else {
            /** @var User */
            $userPart = $this->discord->users->create($data, true);
        }
        $this->discord->users->set($data->id, $userPart);

        return [$userPart, $oldUser];
    }
}
