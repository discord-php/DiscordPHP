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

namespace Discord\Builders\Components;

/**
 * A Text Display is a top-level content component that allows you to add markdown formatted text, including mentions (users, roles, etc) and emojis.
 * 
 * The behavior of this component is extremely similar to the content field of a message, but allows you to add multiple text components, controlling the layout of your message.
 * When sent in a message, pingable mentions (@user, @role, etc) present in this component will ping and send notifications based on the value of the allowed mention object set in message.allowed_mentions.
 *
 * @link https://docs.discord.com/developers/components/reference#text-display
 *
 * @since 10.5.0
 *
 * @property int       $type    10 for text display component.
 * @property ?int|null $id      Optional identifier for component.
 * @property string    $content Text that will be displayed similar to a message.
 */
class TextDisplay extends Content implements Contracts\ComponentV2
{
    public const USAGE = ['Message'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = ComponentObject::TYPE_TEXT_DISPLAY;

    /**
     * Content of the text display.
     *
     * @var string
     */
    protected $content;

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
     * @return self
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
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => $this->type,
            'content' => $this->content,
        ];

        if (isset($this->id)) {
            $data['id'] = $this->id;
        }

        return $data;
    }
}
