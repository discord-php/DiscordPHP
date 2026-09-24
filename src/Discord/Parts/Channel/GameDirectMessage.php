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

namespace Discord\Parts\Channel;

use Carbon\Carbon;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * A direct message sent while at least one of its users has an active Social SDK session.
 *
 * Delivered by the `GAME_DIRECT_MESSAGE_*` webhook events. Between two provisional accounts it is an
 * "SDK DM message", which exists only in-game; otherwise it is an ordinary DM with the channel attached.
 *
 * @since 10.59.0
 *
 * @link https://docs.discord.com/developers/events/webhook-events#sdk-dm-message-object
 *
 * @property      string        $id                  The id of the message.
 * @property      int|null      $type                The message type.
 * @property      string|null   $content             The message content.
 * @property      string        $channel_id          The id of the DM channel.
 * @property-read Channel|null  $channel             The DM channel, with its recipients, when Discord included it.
 * @property      User|null     $author              The user who sent the message.
 * @property      string|null   $recipient_id        The other user in the DM.
 * @property      string|null   $lobby_id            The lobby, for a message in a linked channel.
 * @property      Carbon|null   $timestamp           When the message was sent.
 * @property      Carbon|null   $edited_timestamp    When the message was last edited.
 * @property      int|null      $flags               Message flags combined as a bitfield.
 * @property      string|null   $application_id      The application that sent the message.
 * @property      array|null    $attachments         The message's attachments.
 * @property      array|null    $embeds              The message's embeds.
 * @property      array|null    $components          The message's components.
 * @property      object|null   $activity            Sent with Rich Presence-related chat embeds.
 * @property      object|null   $application         Partial application, sent with Rich Presence-related chat embeds.
 * @property      ?array|null   $moderation_metadata Moderation metadata set with {@see GameDirectMessage::updateModerationMetadata()}.
 */
class GameDirectMessage extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'type',
        'content',
        'channel_id',
        'channel',
        'author',
        'recipient_id',
        'lobby_id',
        'timestamp',
        'edited_timestamp',
        'flags',
        'application_id',
        'attachments',
        'embeds',
        'components',
        'activity',
        'application',
        'moderation_metadata',
    ];

    /**
     * Sets the moderation metadata on this message, which is delivered to the players' clients.
     *
     * Discord clears it whenever the message is edited, and sends `GAME_DIRECT_MESSAGE_UPDATE` so it can be moderated again.
     *
     * @link https://docs.discord.com/developers/discord-social-sdk/how-to/integrate-moderation#applying-moderation-decisions
     *
     * @param array $metadata Up to 5 free-form string key/value pairs describing the decision, e.g. `['action' => 'hide', 'reason' => 'toxicity']`.
     *
     * @return PromiseInterface Rejects with a `\DomainException` when the event did not say who the other user is.
     */
    public function updateModerationMetadata(array $metadata): PromiseInterface
    {
        $author = $this->author?->id;
        $recipient = $this->recipient_id;

        if (null === $author || null === $recipient) {
            return reject(new \DomainException('The message does not name both of its users, so it cannot be moderated.'));
        }

        return $this->discord->private_channels->updateGameDirectMessageModerationMetadata($author, $recipient, $this->id, $metadata);
    }

    /**
     * Gets the recipient_id attribute.
     *
     * @return string|null The other user in the DM, from `recipient_id` or else the channel's recipients.
     */
    protected function getRecipientIdAttribute(): ?string
    {
        if (isset($this->attributes['recipient_id'])) {
            return $this->attributes['recipient_id'];
        }

        $author = $this->author?->id;

        foreach ($this->channel->recipients ?? [] as $recipient) {
            if ($recipient->id === $author) {
                continue;
            }

            return $recipient->id;
        }

        return null;
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel|null The DM channel, with its recipients, when Discord included it.
     */
    protected function getChannelAttribute(): ?Channel
    {
        if (! isset($this->attributes['channel'])) {
            return null;
        }

        return $this->attributePartHelper('channel', Channel::TYPES[$this->attributes['channel']->type ?? Channel::TYPE_DM] ?? Channel::class);
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
