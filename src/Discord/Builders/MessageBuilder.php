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

use Discord\Exceptions\FileNotFoundException;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use JsonSerializable;

/**
 * Helper class used to build messages.
 * 
 * @author David Cole <david.cole1340@gmail.com>
 */
class MessageBuilder implements JsonSerializable
{
    /**
     * Content of the message.
     *
     * @var string|null
     */
    private $content;
    
    /**
     * Whether the message is text-to-speech.
     *
     * @var bool
     */
    private $tts = false;

    /**
     * Array of embeds to send with the message.
     *
     * @var array[]
     */
    private $embeds = [];

    /**
     * Message to reply to with this message.
     *
     * @var Message|null
     */
    private $replyTo;

    /**
     * Files to send with this message.
     *
     * @var array[]
     */
    private $files = [];

    /**
     * Creates a new message builder.
     *
     * @return $this
     */
    public static function new(): static
    {
        return new static();
    }

    /**
     * Sets the content of the message.
     *
     * @param string $content
     *
     * @return $this
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Sets the TTS status of the message.
     *
     * @param bool $tts
     *
     * @return $this
     */
    public function setTts(bool $tts): static
    {
        $this->tts = $tts;

        return $this;
    }

    /**
     * Adds an embed to the message.
     *
     * @param Embed|array $embeds,...
     *
     * @return $this
     */
    public function addEmbed(...$embeds): static
    {
        foreach ($embeds as $embed) {
            if ($embed instanceof Embed) {
                $embed = $embed->getRawAttributes();
            }

            $this->embeds[] = $embed;
        }

        return $this;
    }

    /**
     * Sets the embeds for the message. Clears the existing embeds in the process.
     *
     * @param array $embeds
     *
     * @return $this
     */
    public function setEmbeds(array $embeds): static
    {
        $this->embeds = array_map(function ($embed) {
            if ($embed instanceof Embed) {
                $embed = $embed->getRawAttributes();
            }

            return $embed;
        }, $embeds);

        return $this;
    }

    /**
     * Sets this message as a reply to another message.
     *
     * @param Message|null $message
     *
     * @return $this
     */
    public function setReplyTo(?Message $message): static
    {
        $this->replyTo = $message;

        return $this;
    }

    /**
     * Adds a file attachment to the message.
     *
     * Note this is a synchronous function which uses `file_get_contents` and therefore
     * should not be used when requesting files from an online resource. Fetch the content
     * asynchronously and use the `addFileFromContent` function for tasks like these.
     *
     * @param string      $filepath Path to the file to send.
     * @param string|null $filename Name to send the file as. Null for the base name of `$filepath`.
     *
     * @return $this
     */
    public function addFile(string $filepath, ?string $filename = null): static
    {
        if (! file_exists($filepath)) {
            throw new FileNotFoundException("File does not exist at path {$filepath}.");
        }

        if ($filename == null) {
            $filename = basename($filepath);
        }

        return $this->addFileFromContent($filename, file_get_contents($filepath));
    }

    /**
     * Adds a file attachment to the message with a given filename and content.
     *
     * @param string $filename Name to send the file as.
     * @param string $content  Content of the file.
     *
     * @return $this
     */
    public function addFileFromContent(string $filename, string $content): static
    {
        $this->files[] = [$filename, $content];

        return $this;
    }

    /**
     * Gets the files of the message.
     *
     * @return array
     * @internal
     */
    public function _getFiles(): array
    {
        return $this->files;
    }

    public function jsonSerialize(): array
    {
        $content = [];

        if ($this->content) {
            $content['content'] = $this->content;
        }

        if ($this->tts) {
            $content['tts'] = true;
        }

        if (count($this->embeds) > 0) {
            $content['embeds'] = $this->embeds;
        }

        if ($this->replyTo) {
            $content['message_reference'] = [
                'message_id' => $this->replyTo->id,
                'channel_id' => $this->replyTo->channel_id,
            ];
        }

        return $content;
    }
}
