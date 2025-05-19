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

/**
 * Text display components allow you to send text.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#text-display
 *
 * @since 10.5.0
 */
class TextDisplay extends Content implements Contracts\ComponentV2
{
    public const USAGE = ['Message'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = Component::TYPE_TEXT_DISPLAY;

    /**
     * Content of the text display.
     *
     * @var string
     */
    private $content;

    /**
     * Creates a new text display.
     *
     * @param string $content Content of the text display.
     *
     * @return self
     */
    public static function new(string $content): self
    {
        $component = new self();
        $component->setContent($content);

        return $component;
    }

    /**
     * Sets the content of the text display.
     *
     * @param string $content Content of the text display.
     *
     * @return $this
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Returns the content of the text display.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'content' => $this->content,
        ];
    }
}
