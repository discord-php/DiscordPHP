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

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * An array containing a pinned messages and its pinned_at timestamp.
 *
 * @link https://discord.com/developers/docs/resources/message#message-pin-object
 *
 * @since 10.19.0
 *
 * @property ExCollectionInterface<MessagePin>|MessagePin[] $items
 * @property bool                                           $has_more
 */
class MessagePinData extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'items',
        'has_more',
    ];

    /** @return ExCollectionInterface<MessagePin> */
    protected function getItemsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('items', MessagePin::class);
    }

    /**
     * Returns an iterator for the collection.
     *
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Handles dynamic calls to the collection.
     *
     * @param string $name   Function name.
     * @param array  $params Function parameters.
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->items, $name)) {
            return $this->items->{$name}(...$arguments);
        }

        throw new \BadMethodCallException("Method $name does not exist.");
    }
}
