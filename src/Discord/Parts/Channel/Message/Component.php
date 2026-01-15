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

namespace Discord\Parts\Channel\Message;

use Discord\Builders\Components\ComponentObject;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * Components allow you to style and structure your messages, modals, and interactions.
 * They are interactive elements that can create rich user experiences in your Discord applications.
 *
 * Components are a field on the message object and modal.
 * You can use them when creating messages or responding to an interaction, like an application command.
 *
 * @link https://discord.com/developers/docs/components/reference#what-is-a-component
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
        0 => Component::class, // Fallback for unknown types
        ComponentObject::TYPE_ACTION_ROW => ActionRow::class,
        ComponentObject::TYPE_BUTTON => Button::class,
        ComponentObject::TYPE_STRING_SELECT => StringSelect::class,
        ComponentObject::TYPE_TEXT_INPUT => TextInput::class,
        ComponentObject::TYPE_USER_SELECT => UserSelect::class,
        ComponentObject::TYPE_ROLE_SELECT => RoleSelect::class,
        ComponentObject::TYPE_MENTIONABLE_SELECT => MentionableSelect::class,
        ComponentObject::TYPE_CHANNEL_SELECT => ChannelSelect::class,
        ComponentObject::TYPE_SECTION => Section::class,
        ComponentObject::TYPE_TEXT_DISPLAY => TextDisplay::class,
        ComponentObject::TYPE_THUMBNAIL => Thumbnail::class,
        ComponentObject::TYPE_MEDIA_GALLERY => MediaGallery::class,
        ComponentObject::TYPE_FILE => File::class,
        ComponentObject::TYPE_SEPARATOR => Separator::class,
        ComponentObject::TYPE_CONTAINER => Container::class,
        ComponentObject::TYPE_LABEL => Label::class,
        ComponentObject::TYPE_FILE_UPLOAD => FileUpload::class,
    ];

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'type',
        'id',
    ];

    /**
     * Gets the components.
     *
     * @return ExCollectionInterface<Component>|Component[]
     */
    protected function getComponentsAttribute(): ExCollectionInterface
    {
        return $this->attributeTypedCollectionHelper(Component::class, 'components');
    }

    /**
     * Gets the component.
     */
    public function getComponentAttribute(): ?Component
    {
        return $this->attributePartHelper('component', Component::TYPES[$this->attributes['component']->type ?? 0]);
    }
}
