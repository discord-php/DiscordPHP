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

namespace Discord\Parts\Lobby;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use React\Promise\PromiseInterface;

/**
 * Represents a lobby within Discord. See Managing Lobbies for more information.
 *
 * @since 10.28.0
 *
 * @link https://discord.com/developers/docs/resources/lobby#lobby-object
 *
 * @property      string       $id             The unique identifier of the lobby.
 * @property      string       $application_id The application that created the lobby.
 * @property      array|null   $metadata       Dictionary of string key/value pairs. The max total length is 1000.
 * @property      array        $members        Members of the lobby.
 * @property-read Channel|null $linked_channel The guild channel linked to the lobby.
 */
class Lobby extends Part
{
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

    /**
     * Removes the current user from the specified lobby.
     *
     * It is safe to call this even if the user is no longer a member of the lobby, but will fail if the lobby does not exist.
     */
    public function leave(): PromiseInterface
    {
        return $this->discord->lobbies->leave($this->id);
    }

    /**
     * Gets the linked_channel attribute.
     *
     * @return Channel|null The guild channel linked to the lobby.
     */
    protected function getLinkedChannelAttribute(): ?Channel
    {
        return $this->attributePartHelper('linked_channel', Channel::class);
    }
}
