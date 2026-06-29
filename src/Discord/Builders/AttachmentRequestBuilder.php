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

namespace Discord\Builders;

use JsonSerializable;

/**
 * Helper class used to build attachment request payloads.
 *
 * @link https://docs.discord.com/developers/resources/message#attachment-object-attachment-request-structure
 *
 * @since 10.51.0
 */
class AttachmentRequestBuilder extends Builder implements JsonSerializable
{
    /**
     * Attachment id (snowflake or number).
     *
     * For new attachments this must match the n in files[n].
     *
     * @var int
     */
    protected $id;

    /**
     * Name of the file attached.
     *
     * @var string|null
     */
    protected $filename;

    /**
     * The title of the file.
     *
     * @var string|null
     */
    protected $title;

    /**
     * Description (alt text) for the file (max 1024 characters).
     *
     * @var string|null
     */
    protected $description;

    /**
     * The duration of the audio or video file (required for voice messages).
     *
     * @var float|null
     */
    protected $duration_secs;

    /**
     * Base64 encoded bytearray representing a sampled waveform (required for voice messages).
     *
     * @var string|null
     */
    protected $waveform;

    /**
     * Whether the attachment should be marked as a spoiler and blurred until clicked, this sets the `IS_SPOILER` attachment flag.
     *
     * @var bool|null
     */
    protected $is_spoiler;

    /**
     * Creates a new attachment request builder with a required `id`.
     *
     * For new attachments this must match the n in files[n].
     *
     * @param int $id Attachment id (or files index for new files).
     *
     * @return static
     */
    public static function new(int $id): static
    {
        $instance = new static();
        $instance->setId($id);

        return $instance;
    }

    /**
     * Set the attachment id.
     *
     * For new attachments this must match the n in files[n].
     *
     * @param int $id Attachment id (or files index for new files).
     *
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Returns the attachment id.
     *
     * @return int $id Attachment id (or files index for new files).
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the filename of the attachment.
     *
     * @param string|null $filename Name of the file attached.
     *
     * @return self
     */
    public function setFilename(?string $filename = null): self
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get the filename.
     *
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename ?? null;
    }

    /**
     * Set the title of the file.
     *
     * @param string|null $title The title to set for the attachment.
     *
     * @return self
     */
    public function setTitle(?string $title = null): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the attachment title.
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title ?? null;
    }

    /**
     * Set the description (alt text) for the file.
     *
     * @param string|null $description Description for the file (max 1024 characters).
     *
     * @throws \LengthException Description exceeds 1024 characters.
     *
     * @return self
     */
    public function setDescription(?string $description = null): self
    {
        if ($description !== null && strlen($description) > 1024) {
            throw new \LengthException('Description cannot exceed 1024 characters.');
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Get the description (alt text).
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description ?? null;
    }

    /**
     * Set the duration of the audio or video file in seconds.
     *
     * Required for voice messages.
     *
     * @param float|null $seconds Duration in seconds.
     *
     * @return self
     */
    public function setDurationSecs(?float $seconds = null): self
    {
        $this->duration_secs = $seconds;

        return $this;
    }

    /**
     * Get the duration in seconds.
     *
     * @return float|null
     */
    public function getDurationSecs(): ?float
    {
        return $this->duration_secs ?? null;
    }

    /**
     * Set the waveform data for voice messages.
     *
     * @param string|null $waveform Base64 encoded sampled waveform.
     *
     * @return self
     */
    public function setWaveform(?string $waveform = null): self
    {
        $this->waveform = $waveform;

        return $this;
    }

    /**
     * Get the waveform data.
     *
     * @return string|null
     */
    public function getWaveform(): ?string
    {
        return $this->waveform ?? null;
    }

    /**
     * Mark the attachment as a spoiler.
     *
     * When true, the attachment will be blurred until clicked.
     *
     * @param bool|null $is_spoiler
     *
     * @return self
     */
    public function setIsSpoiler(?bool $is_spoiler = null): self
    {
        $this->is_spoiler = $is_spoiler;

        return $this;
    }

    /**
     * Returns whether the attachment is marked as a spoiler.
     *
     * @return bool|null
     */
    public function getIsSpoiler(): ?bool
    {
        return $this->is_spoiler ?? null;
    }

    /**
     * Returns the array representation of the attachment request used in
     * Message Create/Edit payloads.
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        $data = ['id' => $this->id];

        if ($this->filename !== null) {
            $data['filename'] = $this->filename;
        }

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->duration_secs !== null) {
            $data['duration_secs'] = $this->duration_secs;
        }

        if ($this->waveform !== null) {
            $data['waveform'] = $this->waveform;
        }

        if ($this->is_spoiler !== null) {
            $data['is_spoiler'] = $this->is_spoiler;
        }

        return $data;
    }
}
