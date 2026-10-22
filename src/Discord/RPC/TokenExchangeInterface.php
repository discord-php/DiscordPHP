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

namespace Discord\RPC;

use React\Promise\PromiseInterface;

/**
 * Application-owned asynchronous OAuth exchange. No HTTP or confidential secret is bundled here.
 * A distributed desktop application should delegate confidential exchange to its trusted backend.
 * @since 10.69.0
 */
interface TokenExchangeInterface
{
    /** @return PromiseInterface<string> Nonempty access token; cancellation must abort pending work. */
    public function exchange(string $code, string $clientId, string $redirectUri): PromiseInterface;
}
