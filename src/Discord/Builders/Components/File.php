<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders\Components;

use Discord\Parts\Channel\Attachment;

/**
 * File components allow you to send a file. You can also spoiler it.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#file
 *
 * @since 10.5.0
 */
class File extends Component implements Contracts\ComponentV2
{
    /**
     * The file to be displayed.
     *
     * @var array
     */
    private $file;

    /**
     * Whether the file is a spoiler.
     *
     * @var bool
     */
    private $spoiler = false;

    /**
     * Creates a new file component.
     *
     * @param string|Attachment|null $filename The filename or attachment to reference.
     *
     * @return self
     */
    public static function new(string|Attachment|null $filename = null): self
    {
        $component = new self();

        if ($filename !== null) {
            $component->setFile($filename);
        }

        return $component;
    }

    /**
     * Sets the file to be displayed.
     *
     * @param string|Attachment $filename The filename or attachment to reference.
     *
     * @return $this
     */
    public function setFile(string|Attachment $filename): self
    {
        if ($filename instanceof Attachment) {
            $filename = $filename->filename;
        }

        $this->file = ['url' => "attachment://{$filename}"];

        return $this;
    }

    /**
     * Sets whether the file is a spoiler.
     *
     * @param bool $spoiler Whether the file is a spoiler.
     *
     * @return $this
     */
    public function setSpoiler(bool $spoiler = true): self
    {
        $this->spoiler = $spoiler;

        return $this;
    }

    /**
     * Returns the file reference.
     *
     * @return array
     */
    public function getFile(): array
    {
        return $this->file;
    }

    /**
     * Returns whether the file is a spoiler.
     *
     * @return bool
     */
    public function isSpoiler(): bool
    {
        return $this->spoiler;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => Component::TYPE_FILE,
            'file' => $this->file,
        ];

        if ($this->spoiler) {
            $data['spoiler'] = true;
        }

        return $data;
    }
}
