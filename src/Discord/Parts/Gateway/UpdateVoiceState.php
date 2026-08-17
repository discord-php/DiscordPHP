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

namespace Discord\Parts\Gateway;

use Discord\Parts\Part;

/**
 * Sent when a client wants to join, move, or disconnect from a voice channel.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#update-voice-state
 *
 * @since 10.56.2
 *
 * @property string       $guild_id   ID of the guild.
 * @property ?string|null $channel_id ID of the voice channel client wants to join (null if disconnecting).
 * @property bool         $self_mute  Whether the client is muted.
 * @property bool         $self_deaf  Whether the client deafened.
 */
class UpdateVoiceState extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'guild_id',
        'channel_id',
        'self_mute',
        'self_deaf',
    ];
}
