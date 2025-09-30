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

namespace Discord\Helpers;

use JsonSerializable;

/**
 * Collection of items. Inspired by Laravel Collections.
 *
 * @since 5.0.0 No longer extends Laravel's BaseCollection
 * @since 4.0.0
 */
class ReferenceCollection implements ExCollectionInterface, JsonSerializable
{
    use CollectionTrait;
    /**
     * The collection discriminator.
     *
     * @var ?string
     */
    protected $discrim;

    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items;

    /**
     * Class type allowed into the collection.
     *
     * @var string
     */
    protected $class;

    /**
     * Create a new Collection.
     *
     * @param array   &$items
     * @param ?string $discrim
     * @param ?string $class
     */
    public function __construct(array &$items = [], ?string $discrim = 'id', ?string $class = null)
    {
        $this->items = $items;
        $this->discrim = $discrim;
        $this->class = $class;
    }

    /**
     * Creates a collection from an array.
     *
     * @param array   &$items
     * @param ?string $discrim
     * @param ?string $class
     *
     * @return ExCollectionInterface
     */
    public static function from(array &$items = [], ?string $discrim = 'id', ?string $class = null)
    {
        return new Collection($items, $discrim, $class);
    }

    /**
     * Creates a collection for a class.
     *
     * @param string  $class
     * @param ?string $discrim
     *
     * @return ExCollectionInterface
     */
    public static function for(string $class, ?string $discrim = 'id')
    {
        $items = [];

        return new Collection($items, $discrim, $class);
    }

    /**
     * Fills an array of items into the collection.
     *
     * @param ExCollectionInterface|array &$items
     *
     * @return self
     */
    public function fill(&$items): self
    {
        $items = $items instanceof CollectionInterface
            ? $items->toArray()
            : $items;
        if (! is_array($items)) {
            throw new \InvalidArgumentException('The fill method only accepts arrays or CollectionInterface instances.');
        }

        foreach ($items as $item) {
            $this->pushItem($item);
        }

        return $this;
    }

    /**
     * Pushes items to the collection.
     *
     * @param mixed &...$items
     *
     * @return self
     */
    public function push(&...$items): self
    {
        foreach ($items as $item) {
            $this->pushItem($item);
        }

        return $this;
    }

    /**
     * Pushes a single item to the collection.
     *
     * @param mixed &$item
     *
     * @return self
     */
    public function pushItem(&$item): self
    {
        if (null === $this->discrim) {
            $this->items[] = $item;

            return $this;
        }

        if (null !== $this->class && ! ($item instanceof $this->class)) {
            return $this;
        }

        if (is_array($item)) {
            $this->items[$item[$this->discrim]] = $item;
        } elseif (is_object($item)) {
            $this->items[$item->{$this->discrim}] = $item;
        }

        return $this;
    }
}
