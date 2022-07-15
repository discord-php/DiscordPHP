<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders;

use Discord\Http\Exceptions\RequestFailedException;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Sticker;
use JsonSerializable;

use function Discord\poly_strlen;

/**
 * Helper class used to build normal channel messages.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class MessageBuilder extends AbstractMessageBuilder implements JsonSerializable
{
    use MessageAttributes;

    /**
     * A nonce that can be used for message roundtrips with the gateway (up to 25 characters).
     *
     * @var int|string|null
     */
    private $nonce;

    /**
     * Whether the message is text-to-speech.
     *
     * @var bool|null
     */
    private $tts;

    /**
     * Message to reply to with this message.
     *
     * @var Message|null
     */
    private $replyTo;

    /**
     * IDs of up to 3 stickers in the server to send in the message.
     *
     * @var array|null
     */
    private $sticker_ids = [];

    /**
     * Sets the nonce of the message.
     *
     * @param int|string|null $nonce Nonce of the message. Maximum 25 characters.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setNonce($nonce = null): self
    {
        if (is_string($nonce) && poly_strlen($nonce) > 25) {
            throw new \LengthException('Message nonce must be less than or equal to 25 characters.');
        }

        $this->nonce = $nonce;

        return $this;
    }

    /**
     * Sets the TTS status of the message.
     *
     * @param bool|null $tts
     *
     * @return $this
     */
    public function setTts(?bool $tts = null): self
    {
        $this->tts = $tts;

        return $this;
    }

    /**
     * Returns the value of TTS of the message.
     *
     * @return bool
     */
    public function getTts(): bool
    {
        return $this->tts ?? false;
    }

    /**
     * Sets this message as a reply to another message.
     *
     * @param Message|null $message
     *
     * @return $this
     */
    public function setReplyTo(?Message $message): self
    {
        $this->replyTo = $message;

        return $this;
    }

    /**
     * Adds a sticker to the message.
     *
     * @param string|Sticker $sticker Sticker to add.
     *
     * @throws \OverflowException
     *
     * @return $this
     */
    public function addSticker($sticker): self
    {
        if ($sticker instanceof Sticker) {
            $sticker = $sticker->id;
        }

        if (count($this->sticker_ids) >= 3) {
            throw new \OverflowException('You can only add 3 stickers to a message');
        }

        $this->sticker_ids[] = $sticker;

        return $this;
    }

    /**
     * Removes a sticker from the message.
     *
     * @param string|Sticker $sticker Sticker to remove.
     *
     * @return $this
     */
    public function removeSticker($sticker): self
    {
        if ($sticker instanceof Sticker) {
            $sticker = $sticker->id;
        }

        if (($idx = array_search($sticker, $this->sticker_ids)) !== null) {
            array_splice($this->sticker_ids, $idx, 1);
        }

        return $this;
    }

    /**
     * Sets the stickers of the message. Removes the existing stickers in the process.
     *
     * @param array $stickers New message stickers.
     *
     * @return $this
     */
    public function setStickers(array $stickers): self
    {
        $this->sticker_ids = [];

        foreach ($stickers as $sticker) {
            $this->addSticker($sticker);
        }

        return $this;
    }

    /**
     * Returns all the sticker IDs in the message.
     *
     * @return Sticker[]
     */
    public function getStickers(): array
    {
        return $this->sticker_ids;
    }

    /**
     * Returns a boolean that determines whether the message needs to
     * be sent via multipart request, i.e. contains files.
     *
     * @return bool
     */
    public function requiresMultipart(): bool
    {
        return count($this->files);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        $empty = count($this->files) < 1;
        $payload = [
            'components' => $this->components,
        ];

        if ($this->content) {
            $payload['content'] = $this->content;
            $empty = false;
        }

        if (isset($this->nonce)) {
            $payload['nonce'] = $this->nonce;
        }

        if ($this->tts) {
            $payload['tts'] = true;
        }

        if ($this->flags) {
            $payload['flags'] = $this->flags;
        }

        if ($this->allowed_mentions) {
            $payload['allowed_mentions'] = $this->allowed_mentions;
        }

        if (count($this->embeds)) {
            $payload['embeds'] = $this->embeds;
            $empty = false;
        }

        if (count($this->sticker_ids)) {
            $payload['sticker_ids'] = $this->sticker_ids;
            $empty = false;
        }

        if ($this->replyTo) {
            $payload['message_reference'] = [
                'message_id' => $this->replyTo->id,
                'channel_id' => $this->replyTo->channel_id,
            ];
        }

        if ($this->attachments) {
            $payload['attachments'] = $this->attachments;
        }

        if ($empty) {
            throw new RequestFailedException('You cannot send an empty message. Set the content or add an embed or file.');
        }

        return $payload;
    }
}
