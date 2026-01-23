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

namespace Discord\WebSockets\Events\Data;

use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\Websockets\PresenceUpdate;

/**
 * Raw data received from the `GUILD_MEMBERS_CHUNK` event.
 *
 * @see Discord::handleGuildMembersChunk()
 *
 * @link https://discord.com/developers/docs/events/gateway-events#guild-members-chunk-guild-members-chunk-event-fields
 *
 * @since 10.38.2
 *
 * @property string                $guild_id    ID of the guild.
 * @property Member[]              $members     Set of guild members.
 * @property int                   $chunk_index Chunk index in the expected chunks for this response (0 <= chunk_index < chunk_count).
 * @property int                   $chunk_count Total number of expected chunks for this response.
 * @property array|null            $not_found   When passing an invalid ID to `REQUEST_GUILD_MEMBERS`, it will be returned here.
 * @property PresenceUpdate[]|null $presences   When passing `true` to `REQUEST_GUILD_MEMBERS`, presences of the returned members will be here.
 * @property string|null           $nonce       Nonce used in the Guild Members Request.
 */
class GuildMembersChunkData extends Part
{
    protected $fillable = [
        'guild_id',
        'members',
        'chunk_index',
        'chunk_count',
        'not_found',
        'presences',
        'nonce',
    ];
}
