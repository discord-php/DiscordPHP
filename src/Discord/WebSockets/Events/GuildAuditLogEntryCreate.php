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
use Discord\Parts\Guild\AuditLog\Entry;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-audit-log-entry-create
 *
 * @since 10.0.0
 */
class GuildAuditLogEntryCreate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var Entry */
        $entryPart = $this->factory->part(Entry::class, (array) $data, true);

        return $entryPart;
    }
}
