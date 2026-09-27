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

namespace Discord\WebhookEvents;

use Discord\Parts\Part;

/**
 * Remembers recently delivered events, so copies of them can be dropped.
 *
 * Copies come from Discord retrying a webhook, and from entitlement events, which arrive over both the gateway
 * and webhooks. Webhooks are not realtime, so the gateway's copy usually comes first.
 *
 * @internal Used by {@see WebhookEventReceiver}.
 *
 * @since 10.59.0
 */
final class DeliveredEvents
{
    /**
     * The events delivered, oldest first.
     *
     * @var array<string, float> Event key => when it was delivered.
     */
    private array $delivered = [];

    /**
     * @param int $seconds How long an event is remembered: by default, as long as Discord retries one.
     * @param int $limit   The most events remembered at once.
     */
    public function __construct(
        private readonly int $seconds = 600,
        private readonly int $limit = 10000,
    ) {
    }

    /**
     * Remembers a delivered event.
     *
     * @param string $key The event's key.
     *
     * @return bool Whether it was new.
     */
    public function remember(string $key): bool
    {
        $now = microtime(true);

        foreach ($this->delivered as $old => $at) {
            if ($at > $now - $this->seconds && count($this->delivered) < $this->limit) {
                break;
            }

            unset($this->delivered[$old]);
        }

        if (isset($this->delivered[$key])) {
            return false;
        }

        $this->delivered[$key] = $now;

        return true;
    }

    /**
     * Identifies an entitlement event the same way whether it came from the gateway or a webhook.
     *
     * @param string                 $event       The event type.
     * @param Part|object|array|null $entitlement The entitlement, as a part or as raw data.
     */
    public static function entitlementKey(string $event, $entitlement): string
    {
        $data = $entitlement instanceof Part ? $entitlement->getRawAttributes() : (array) $entitlement;

        // The part may hold a parsed date, and the two sources need not format it alike.
        $ends = $data['ends_at'] ?? null;
        $ends = match (true) {
            $ends instanceof \DateTimeInterface => $ends->getTimestamp(),
            is_string($ends) => strtotime($ends),
            default => $ends,
        };

        return implode(':', [
            $event,
            $data['id'] ?? '',
            (int) ($data['consumed'] ?? false),
            (int) ($data['deleted'] ?? false),
            $ends ?? '',
        ]);
    }
}
