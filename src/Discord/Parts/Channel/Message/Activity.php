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

namespace Discord\Parts\Channel\Message;

use Discord\Parts\Part;

/**
 * Sent with Rich Presence-related chat embeds.
 *
 * @link https://discord.com/developers/docs/resources/message#message-object-message-activity-structure
 *
 * @since 10.22.0
 *
 * @property int         $type     Type of message activity
 * @property string|null $party_id Party ID from a Rich Presence event
 */
class Activity extends Part
{
    public const TYPE_JOIN = 1;
    public const TYPE_SPECTATE = 2;
    public const TYPE_LISTEN = 3;
    public const TYPE_JOIN_REQUEST = 5;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'type',
        'party_id',
    ];
}
