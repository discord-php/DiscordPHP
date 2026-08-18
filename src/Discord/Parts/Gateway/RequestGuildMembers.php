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

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Used to request all members for a guild or a list of guilds.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#request-guild-members
 *
 * @since 10.56.3
 *
 * @property string             $guild_id  ID of the guild to get members for.
 * @property string             $query     String that username starts with, or an empty string to return all members. Required when not including `user_ids`.
 * @property int                $limit     Maximum number of members to send matching the `query`; a limit of `0` can be used with an empty string `query` to return all members. Required when including a `query`.
 * @property ?bool|null         $presences Whether to include presences of matched members.
 * @property ?string|array|null $user_ids  Snowflake or array of snowflakes to specify which users to fetch. Required when not including a `query`.
 * @property ?string|null       $nonce     Nonce to identify the Guild Members Chunk response.
 *
 * @property-read ?Guild                             $guild The guild to get members for.
 * @property-read ExCollectionInterface<User>|User[] $users The users of the `user_ids`.
 */
class RequestGuildMembers extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'guild_id',
        'query',
        'limit',
        'presences',
        'user_ids',
        'nonce',
    ];

    /**
     * Gets the limit attribute, defaulting to 0 if not set.
     *
     * @return int The limit value, or 0 if not set.
     */
    protected function getLimitAttribute(): int
    {
        return $this->attributes['limit'] ?? 0;
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the users of the user IDs.
     *
     * @return ExCollectionInterface<User>
     */
    protected function getUsersAttribute(): ExCollectionInterface
    {
        /** @var ExCollectionInterface $users */
        $users = $this->discord->getCollectionClass()::for(User::class);

        foreach ((array) ($this->user_ids ?? []) as $user_id) {
            $users->push($this->discord->users->get('id', $user_id) ?? $this->discord->users->create(['id' => $user_id], true));
        }

        return $users;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $data = [
            'guild_id' => $this->guild_id,
        ];

        if (isset($this->attributes['user_ids'])) {
            $data['user_ids'] = is_array($this->attributes['user_ids'])
                ? array_values($this->attributes['user_ids'])
                : [$this->attributes['user_ids']];
        } else {
            $data['query'] = $this->attributes['query'] ?? '';
            $data['limit'] = $this->limit;
        }

        if (isset($this->attributes['presences'])) {
            $data['presences'] = (bool) $this->attributes['presences'];
        }

        if (isset($this->attributes['nonce'])) {
            $data['nonce'] = (string) $this->attributes['nonce'];
        }

        return $data;
    }
}
