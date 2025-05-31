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

namespace Discord\Parts\Channel\Message;

use Discord\Builders\Components\Component as ComponentBuilder;
use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * Components allow you to style and structure your messages, modals, and interactions.
 * They are interactive elements that can create rich user experiences in your Discord applications.
 *
 * Components are a field on the message object and modal.
 * You can use them when creating messages or responding to an interaction, like an application command.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#what-is-a-component
 *
 * @since 10.11.0
 *
 * @property int         $type The type of the component.
 * @property string|null $id   32 bit integer used as an optional identifier for component.
 */
class Component extends Part
{
    /**
     * Available components and their respective classes.
     *
     * @var array<int, string>
     */
    public const TYPES = [
        0                                         => Component::class, // Fallback for unknown types
        ComponentBuilder::TYPE_ACTION_ROW         => ActionRow::class,
        ComponentBuilder::TYPE_BUTTON             => Button::class,
        ComponentBuilder::TYPE_STRING_SELECT      => StringSelect::class,
        ComponentBuilder::TYPE_TEXT_INPUT         => TextInput::class,
        ComponentBuilder::TYPE_USER_SELECT        => UserSelect::class,
        ComponentBuilder::TYPE_ROLE_SELECT        => RoleSelect::class,
        ComponentBuilder::TYPE_MENTIONABLE_SELECT => MentionableSelect::class,
        ComponentBuilder::TYPE_CHANNEL_SELECT     => ChannelSelect::class,
        ComponentBuilder::TYPE_SECTION            => Section::class,
        ComponentBuilder::TYPE_TEXT_DISPLAY       => TextDisplay::class,
        ComponentBuilder::TYPE_THUMBNAIL          => Thumbnail::class,
        ComponentBuilder::TYPE_MEDIA_GALLERY      => MediaGallery::class,
        ComponentBuilder::TYPE_FILE               => File::class,
        ComponentBuilder::TYPE_SEPARATOR          => Separator::class,
        ComponentBuilder::TYPE_CONTAINER          => Container::class
    ];

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id'
    ];

    /**
     * Gets the components of the interaction.
     *
     * @return ExCollectionInterface|Component[]|null $components
     */
    protected function getComponentsAttribute(): ?ExCollectionInterface
    {
        if (! isset($this->attributes['components'])) {
            return null;
        }

        $components = Collection::for(Component::class, null);

        foreach ($this->attributes['components'] as $component) {
            $components->pushItem($this->createOf(self::TYPES[$component->type ?? 0], $component));
        }

        return $components;
    }
}
