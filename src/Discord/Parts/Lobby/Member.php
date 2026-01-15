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

namespace Discord\Parts\Lobby;

use Discord\Parts\Part;

/**
 * Represents a member of a lobby, including optional metadata and flags.
 *
 * @since 10.28.0
 *
 * @link https://discord.com/developers/docs/resources/lobby#lobby-object
 *
 * @property string      $id       The unique identifier of the user.
 * @property ?array|null $metadata Dictionary of string key/value pairs. The max total length is 1000.
 * @property ?int|null   $flags    Lobby member flags combined as a bitfield.
 */
class Member extends Part
{
    /** User can link a text channel to a lobby. */
    public const FLAG_CAN_LINK_LOBBY = 1 << 0;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'application_id',
        'metadata',
        'members',
        'linked_channel',
    ];
}
