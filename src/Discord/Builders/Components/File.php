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

namespace Discord\Builders\Components;

use Discord\Parts\Channel\Attachment;

/**
 * File components allow you to send a file. You can also spoiler it.
 *
 * @link https://discord.com/developers/docs/components/reference#file
 *
 * @since 10.5.0
 *
 * @property int        $type    13 for a file component
 * @property ?int|null  $id      Optional identifier for component
 * @property array      $file    This unfurled media item only supports attachment references using the attachment://<filename> syntax
 * @property ?bool|null $spoiler Whether the media should be a spoiler (blurred out). Defaults to false
 */
class File extends Content implements Contracts\ComponentV2
{
    public const USAGE = ['Message'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = ComponentObject::TYPE_FILE;

    /**
     * The file to be displayed.
     *
     * @var array
     */
    protected $file;

    /**
     * Whether the file is a spoiler.
     *
     * @var bool|null
     */
    protected $spoiler;

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
     * @param Attachment|string $filename The filename or attachment to reference.
     *
     * @return $this
     */
    public function setFile(Attachment|string $filename): self
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
     * @param bool|null $spoiler Whether the file is a spoiler.
     *
     * @return $this
     */
    public function setSpoiler(?bool $spoiler = true): self
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
        return $this->spoiler ?? false;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
            'file' => $this->file,
        ];

        if (isset($this->spoiler)) {
            $content['spoiler'] = $this->spoiler;
        }

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        return $content;
    }
}
