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

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * A direct message sent while at least one of its users has an active Social SDK session.
 *
 * Delivered by the `GAME_DIRECT_MESSAGE_*` webhook events: a message with the DM channel and its recipients
 * attached. Between two provisional accounts it is an "SDK DM message", which exists only in-game and carries
 * a subset of a message's fields.
 *
 * The bot is not in the DM, so the methods it inherits that act through the channel, such as `reply()`,
 * `edit()`, `delete()` and `react()`, reject with a `\BadMethodCallException` rather than send a request
 * Discord would refuse. Moderate it with {@see GameDirectMessage::updateModerationMetadata()}.
 *
 * @since 10.59.0
 *
 * @link https://docs.discord.com/developers/events/webhook-events#message-object
 * @link https://docs.discord.com/developers/events/webhook-events#sdk-dm-message-object
 *
 * @property-read Channel     $channel             The DM channel, with its recipients when Discord included them.
 * @property      string|null $recipient_id        The other user in the DM.
 * @property      string|null $lobby_id            The lobby, for a message in a linked channel.
 * @property      ?array|null $moderation_metadata Moderation metadata set with {@see GameDirectMessage::updateModerationMetadata()}.
 */
class GameDirectMessage extends Message
{
    /**
     * The fields Discord adds to a message for these events.
     */
    protected const GAME_FILLABLE = [
        'channel',
        'recipient_id',
        'lobby_id',
        'moderation_metadata',
    ];

    /**
     * @inheritDoc
     */
    public function __construct(Discord $discord, array $attributes = [], bool $created = false)
    {
        $this->fillable = array_merge($this->fillable, self::GAME_FILLABLE);

        parent::__construct($discord, $attributes, $created);
    }

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
        $author = $this->user_id;
        $recipient = null === $author ? null : $this->recipient_id;

        if (null === $author || null === $recipient) {
            return reject(new \DomainException('The message does not name both of its users, so it cannot be moderated.'));
        }

        return $this->discord->private_channels->updateGameDirectMessageModerationMetadata($author, $recipient, $this->id, $metadata);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function startThread(array|string $options, string|null|int $reason = null, ?string $_reason = null): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function reply($message): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function crosspost(): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`, and sets no timer.
     */
    public function delayedReply($message, int $delay, &$timer = null): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`, and sets no timer.
     */
    public function delayedDelete(int $delay, &$timer = null): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function react($emoticon): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function deleteReaction(int $type, $emoticon = null, ?string $id = null): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function deleteAllReactions(): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function deleteOwnReaction($emoticon): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function deleteUserReaction($emoticon, string $user_id): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function deleteEmojiReactions($emoticon): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function edit(MessageBuilder $message): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function delete(?string $reason = null): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot receives no reactions from the DM, so the collector would never collect anything.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function createReactionCollector(callable $filter, array $options = []): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function addEmbed(Embed $embed): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function save(?string $reason = null): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * Not available: the bot is not in the DM.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    public function fetch(): PromiseInterface
    {
        return $this->notInChannel(__FUNCTION__);
    }

    /**
     * The bot cannot delete a message in a DM it is not in.
     *
     * @return bool Always false.
     */
    public function isDeletable(): bool
    {
        return false;
    }

    /**
     * Rejects a call that would act through the DM channel.
     *
     * @param string $method The method called.
     *
     * @return PromiseInterface Rejects with a `\BadMethodCallException`.
     */
    protected function notInChannel(string $method): PromiseInterface
    {
        return reject(new \BadMethodCallException("{$method}() acts through the DM channel, which the bot is not in. A game direct message can only be moderated, with updateModerationMetadata()."));
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

        // Without one, the stand-in DM that Message builds lists only the author.
        if (! isset($this->attributes['channel'])) {
            return null;
        }

        foreach ($this->channel->recipients as $recipient) {
            if ($recipient->id === $this->user_id) {
                continue;
            }

            return $recipient->id;
        }

        return null;
    }

    /**
     * Gets the channel attribute.
     *
     * Always a DM or group DM, never a thread, so it narrows {@see Message}'s `Channel|Thread`.
     *
     * @return Channel The DM channel Discord attached, else the cached private channel, else a DM with only the author.
     */
    protected function getChannelAttribute(): Channel
    {
        if (isset($this->attributes['channel'])) {
            $type = $this->attributes['channel']->type ?? Channel::TYPE_DM;

            return $this->attributePartHelper('channel', Channel::TYPE_GROUP_DM === $type ? GroupDM::class : DM::class);
        }

        return $this->discord->private_channels->get('id', $this->channel_id)
            ?? $this->factory->part(DM::class, [
                'id' => $this->channel_id,
                'type' => Channel::TYPE_DM,
                'recipients' => [$this->author],
            ], true);
    }
}
