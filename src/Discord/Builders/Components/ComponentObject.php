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

/**
 * Components allow you to style and structure your messages, modals, and interactions.
 * They are interactive elements that can create rich user experiences in your Discord applications.
 *
 * Components are a field on the message object and modal.
 * You can use them when creating messages or responding to an interaction, like an application command.
 *
 * @link https://discord.com/developers/docs/components/reference#component-object
 *
 * @since 10.9.0
 */
abstract class ComponentObject extends Component
{
    public const USAGE = [];

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
}
