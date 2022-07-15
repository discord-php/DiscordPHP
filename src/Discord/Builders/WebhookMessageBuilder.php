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
use JsonSerializable;

use function Discord\poly_strlen;

/**
 * Helper class used to build webhook messages.
 */
class WebhookMessageBuilder extends AbstractMessageBuilder implements JsonSerializable
{
    use MessageAttributes;

    /**
     * Whether the message is text-to-speech.
     *
     * @var bool|null
     */
    private $tts;

    /**
     * Override the default username of the webhook.
     * 
     * @var string|null
     */
    private $username;

    /**
     * Override the default avatar of the webhook.
     * 
     * @var string|null
     */
    private $avatar_url;

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
     * Override the default username of the webhook. (Cannot be used to edit)
     *
     * @param string|null $username
     *
     * @throws \LengthException
     * 
     * @return $this
     */
    public function setUsername(?string $username = null): self
    {
        if (isset($username) && poly_strlen($username) > 80) {
            throw new \LengthException('Username can be only up to 80 characters.');
        }

        $this->username = $username;

        return $this;
    }

    /**
     * Returns the override value of the webhook message username.
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username ?? null;
    }

    /**
     * Override the default avatar URL of the webhook. (Cannot be used to edit)
     *
     * @param string|null $avatar_url
     *
     * @return $this
     */
    public function setAvatarUrl(?string $avatar_url = null): self
    {
        $this->avatar_url = $avatar_url;

        return $this;
    }

    /**
     * Returns the override value of the webhook message avatar URL.
     *
     * @return string|null
     */
    public function getAvatarUrl(): ?string
    {
        return $this->avatar_url ?? null;
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

        if (isset($this->username)) {
            $payload['username'] = $this->username;
        }

        if (isset($this->avatar_url)) {
            $payload['avatar_url'] = $this->avatar_url;
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

        if ($this->attachments) {
            $payload['attachments'] = $this->attachments;
        }

        if ($empty) {
            throw new RequestFailedException('You cannot send an empty message. Set the content or add an embed or file.');
        }

        return $payload;
    }
}
