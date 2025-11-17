<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\AuditLog\AuditLog;
use Discord\WebSockets\Event;
use Discord\Parts\Guild\AuditLog\Entry;
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-audit-log-entry-create
 *
 * @since 10.0.0
 */
class GuildAuditLogEntryCreate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        /** @var Entry */
        $entryPart = $this->factory->part(Entry::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id ?? '')) {
            /** @var Guild $guild */
            /** @var ?AuditLog */
            if ($audit_log = yield $guild->audit_log->cacheGet('')) {
                /** @var AuditLog $audit_log */
                $entries = $audit_log->audit_log_entries;
                $entries->pushItem($entryPart);
                $audit_log->audit_log_entries = $entries;
                yield $guild->audit_log->cache->set('', $audit_log);
            }
        }

        return $entryPart;
    }
}
