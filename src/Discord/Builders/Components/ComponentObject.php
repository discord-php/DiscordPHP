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
 * Components allow you to style and structure your messages, modals, and interactions.
 * They are interactive elements that can create rich user experiences in your Discord applications.
 *
 * Components are a field on the message object and modal.
 * You can use them when creating messages or responding to an interaction, like an application command.
 *
 * @link https://discord.com/developers/docs/components/reference#component-object
 *
 * @since 10.45.12 Class is concrete instead of abstract.
 * @since 10.9.0
 */
class ComponentObject extends Component
{
    /**
     * Usage contexts for the component. 
     *
     * @var string[]
     */    
    public const USAGE = [];

    /**
     * Available components and their respective classes.
     *
     * @var array<int, string>
     */
    public const TYPES = [
        0 => ComponentObject::class, // Fallback for unknown types
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
        ComponentObject::TYPE_RADIO_GROUP => RadioGroup::class,
        ComponentObject::TYPE_CHECKBOX_GROUP => CheckboxGroup::class,
        ComponentObject::TYPE_CHECKBOX => Checkbox::class,
    ];

    /** Container to display a row of interactive components. */
    public const TYPE_ACTION_ROW = 1;
    /** Button object. */
    public const TYPE_BUTTON = 2;
    /** Select menu for picking from defined text options. */
    public const TYPE_STRING_SELECT = 3;
    /** Text input object. */
    public const TYPE_TEXT_INPUT = 4;
    /** Select menu for users. */
    public const TYPE_USER_SELECT = 5;
    /** Select menu for roles. */
    public const TYPE_ROLE_SELECT = 6;
    /** Select menu for mentionables (users and roles). */
    public const TYPE_MENTIONABLE_SELECT = 7;
    /** Select menu for channels. */
    public const TYPE_CHANNEL_SELECT = 8;

    // Requires IS_COMPONENTS_V2 flag
    /** Container to display text alongside an accessory component. */
    public const TYPE_SECTION = 9;
    /** Markdown text. */
    public const TYPE_TEXT_DISPLAY = 10;
    /** Small image that can be used as an accessory. */
    public const TYPE_THUMBNAIL = 11;
    /** Display images and other media. */
    public const TYPE_MEDIA_GALLERY = 12;
    /** Displays an attached file. */
    public const TYPE_FILE = 13;
    /** Component to add vertical padding between other components. */
    public const TYPE_SEPARATOR = 14;
    /** Undocumented. */
    public const TYPE_CONTENT_INVENTORY_ENTRY = 16;
    /** Container that visually groups a set of components. */
    public const TYPE_CONTAINER = 17;

    /** Container associating a label and description with a component. */
    public const TYPE_LABEL = 18;
    /** Component for uploading files. */
    public const TYPE_FILE_UPLOAD = 19;
    /** Undocumented. Used by the client for checkpoint. */
    public const TYPE_CHECKPOINT_CARD = 20;

    /** Single-choice set of radio options. */
    public const TYPE_RADIO_GROUP = 21;
    /** Multi-select group of checkboxes. */
    public const TYPE_CHECKBOX_GROUP = 22;
    /** Single checkbox for binary choice. */
    public const TYPE_CHECKBOX = 23;

    /**
     * The type of the component.
     *
     * @var int
     */
    protected $type;

    /**
     * 32 bit integer used as an optional identifier for component.
     *
     * @var int|null
     */
    protected $id;

    /**
     * Creates a new component.
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Retrieves the type of the component.
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * The id field is optional and is used to identify components in the response from an interaction. The id must be unique within the message and is generated sequentially if left empty. Generation of ids won't use another id that exists in the message if you have one defined for another component. Sending components with an id of 0 is allowed but will be treated as empty and replaced by the API. 	32 bit integer used as an optional identifier for component.
     *
     * @param string|null $id 32 bit integer used as an optional identifier for component.
     */
    protected function setId(?string $id = null): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED);
        
        $result = [];
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);
            if ($value !== null) {
                $result[$property->getName()] = $value;
            }
        }
        
        return $result;
    }
}
