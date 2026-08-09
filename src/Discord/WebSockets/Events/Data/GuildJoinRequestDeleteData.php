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

use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Raw data received from the `GUILD_JOIN_REQUEST_DELETE` event.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#guild-join-request-delete
 *
 * @since 10.55.0
 *
 * @property string $id       ID of the join request
 * @property string $guild_id ID of the guild
 * @property string $user_id  ID of the applicant
 *
 * @property Guild|null $guild The guild this event belongs to
 * @property User|null  $user  The user who applied
 */
class GuildJoinRequestDeleteData extends Part
{
    /** @inheritDoc */
    protected $fillable = [
        'id',
        'guild_id',
        'user_id',
    ];

    /**
     * Gets the guild attribute.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        if ($this->guild_id === null) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the user attribute.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }
}
