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

use Discord\Builders\ChannelBuilder;
use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Event;

/**
 * A channel the bot can no longer view arrives obfuscated (see `Channel::isObfuscated()`); the cached copy is
 * cleared down to the fields Discord keeps. Regaining view sends the full channel again.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#channel-update
 * @link https://docs.discord.com/developers/resources/channel#channel-object-obfuscated-channels
 *
 * @since 2.1.3
 */
class ChannelUpdate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        $oldChannel = $repository = null;

        /** @var Channel */
        $channelPart = $this->factory->part(ChannelBuilder::TYPES[$data->type] ?? Channel::class, (array) $data, true);

        if ($channelPart->is_private) {
            /** @var ?Channel */
            if (! $oldChannel = yield $this->discord->private_channels->cacheGet($data->id)) {
                $repository = $this->discord->private_channels;
            }
        } elseif ($guild = $channelPart->guild) {
            /** @var ?Channel */
            if (! $oldChannel = yield $guild->channels->cacheGet($data->id)) {
                $repository = $guild->channels;
            }
        }

        if ($oldChannel) {
            // Swap
            $channelPart = $oldChannel;
            $oldChannel = clone $oldChannel;

            $attributes = (array) $data;
            if ($channelPart instanceof Channel && $channelPart->isObfuscated() !== (bool) (($attributes['flags'] ?? 0) & Channel::FLAG_CHANNEL_OBFUSCATED)) {
                // The bot lost or regained view of the channel. Clear what the cached copy held, so fields the
                // new payload leaves out are not carried over from the other side of the change.
                $attributes += array_fill_keys(array_diff(array_keys($channelPart->getRawAttributes()), Channel::OBFUSCATED_KEPT_FIELDS, ['is_private']), null);
            }

            $channelPart->fill($attributes);
        }

        if ($repository) {
            $repository->set($data->id, $channelPart);
        }

        return [$channelPart, $oldChannel];
    }
}
