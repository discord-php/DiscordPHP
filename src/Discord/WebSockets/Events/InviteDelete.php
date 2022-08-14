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
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;

use function React\Async\coroutine;

/**
 * @see https://discord.com/developers/docs/topics/gateway#invite-delete
 */
class InviteDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $invitePart = null;

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?Channel */
                if ($channel = yield $guild->channels->cacheGet($data->channel_id)) {
                    $invitePart = yield $channel->invites->cachePull($data->code);
                }

                if (! $invitePart) {
                    $invitePart = yield $guild->invites->cachePull($data->code);
                }
            }

            return $invitePart ?? $data;
        }, $data)->then([$deferred, 'resolve']);
    }
}
