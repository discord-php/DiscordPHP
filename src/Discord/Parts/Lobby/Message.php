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

use Carbon\Carbon;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;

/**
 * A message sent in a lobby through the Social SDK.
 *
 * @since 10.59.0
 *
 * @link https://docs.discord.com/developers/resources/lobby#lobby-message-object
 *
 * @property      string       $id                  The id of the message.
 * @property      int          $type                The message type.
 * @property      string       $content             The message content.
 * @property      string       $lobby_id            The id of the lobby this message was sent to.
 * @property      string       $channel_id          Equal to `lobby_id`; included for compatibility with the messages interface.
 * @property      User         $author              The user who sent the message.
 * @property      ?array|null  $lobby_member        Contains the author's lobby member `additional_name`, when they have one.
 * @property      ?array|null  $metadata            Dispatch-only metadata sent with the message.
 * @property      ?array|null  $moderation_metadata Moderation metadata set with {@see \Discord\Repository\LobbyRepository::updateMessageModerationMetadata()}.
 * @property      int          $flags               Message flags combined as a bitfield.
 * @property      string       $application_id      The application that sent the message.
 * @property      Carbon|null  $timestamp           When the message was sent; sent with `LOBBY_MESSAGE_UPDATE`.
 * @property      Carbon|null  $edited_timestamp    When the message was last edited; sent with `LOBBY_MESSAGE_UPDATE`.
 * @property-read Lobby|null   $lobby               The lobby, when it is cached.
 */
class Message extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'type',
        'content',
        'lobby_id',
        'channel_id',
        'author',
        'lobby_member',
        'metadata',
        'moderation_metadata',
        'flags',
        'application_id',
        'timestamp',
        'edited_timestamp',
    ];

    /**
     * Sets the moderation metadata on this message, which is delivered to the players' clients.
     *
     * @param array $metadata Up to 5 free-form string key/value pairs describing the decision, e.g. `['action' => 'hide', 'reason' => 'toxicity']`.
     *
     * @return PromiseInterface
     */
    public function updateModerationMetadata(array $metadata): PromiseInterface
    {
        return $this->discord->lobbies->updateMessageModerationMetadata($this->lobby_id, $this->id, $metadata);
    }

    /**
     * Gets the author attribute.
     *
     * @return User|null The user who sent the message.
     */
    protected function getAuthorAttribute(): ?User
    {
        return $this->attributePartHelper('author', User::class);
    }

    /**
     * Gets the lobby attribute.
     *
     * @return Lobby|null The lobby the message was sent to, when it is cached.
     */
    protected function getLobbyAttribute(): ?Lobby
    {
        return $this->discord->lobbies->get('id', $this->lobby_id);
    }

    /**
     * Gets the timestamp attribute.
     *
     * @return Carbon|null When the message was sent.
     */
    protected function getTimestampAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('timestamp');
    }

    /**
     * Gets the edited_timestamp attribute.
     *
     * @return Carbon|null When the message was last edited.
     */
    protected function getEditedTimestampAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('edited_timestamp');
    }
}
