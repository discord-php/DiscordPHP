<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders;

use Discord\Parts\Embed\Embed;

class MessageBuilder
{
    protected $content;
    protected $attachments = [];
    protected $tts = false;

    public function __construct(?string $content = null)
    {
        $this->content = $content;
    }

    public static function new(?string $content = null): self
    {
        return new static($content);
    }

    /**
     * Sets the TTS value of the message.
     *
     * @param bool $tts
     *
     * @return self
     */
    public function tts(bool $tts = true): self
    {
        $this->tts = $tts;

        return $this;
    }

    /**
     * Adds an embed to the message.
     * You may only have one embed per message.
     *
     * @param Embed|array $embed
     *
     * @return self
     */
    public function embed($embed): self
    {
        if ($embed instanceof Embed) {
            $embed = $embed->getRawAttributes();
        }

        $this->embed = $embed;

        return $this;
    }

    /**
     * Adds an attachment to the message.
     *
     * @param string $path Path to the attachment.
     * @param string $name Name of the attachment.
     *
     * @return self
     */
    public function addAttachment(string $path, string $name): self
    {
        $this->attachments[] = [$path, $name];

        return $this;
    }
}
