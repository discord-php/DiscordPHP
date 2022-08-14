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
use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Command\Overwrite;

use function React\Async\coroutine;

/**
 * @see https://discord.com/developers/docs/topics/gateway#integration-update
 */
class ApplicationCommandPermissionsUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $overwritePart = $oldOverwrite = null;

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?Command */
                if ($command = yield $guild->commands->cacheGet($data->id)) {
                    // There is only one command permissions object
                    /** @var Overwrite */
                    if ($oldOverwrite = $command->overwrites[$data->id]) {
                        // Swap
                        $overwritePart = $oldOverwrite;
                        $oldOverwrite = clone $oldOverwrite;

                        $overwritePart->fill((array) $data);
                    }
                }
            }

            if ($overwritePart === null) {
                /** @var Overwrite */
                $overwritePart = $this->factory->create(Overwrite::class, $data, true);
            }

            if ($guild && isset($command)) {
                // There is only one command permissions object
                yield $command->overwrites->cache->set($data->id, $overwritePart);
            }

            return [$overwritePart, $oldOverwrite];
        }, $data)->then([$deferred, 'resolve']);
    }
}
