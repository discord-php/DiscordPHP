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
class Collection implements ExCollectionInterface, JsonSerializable
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
     * @param array   $items
     * @param ?string $discrim
     * @param ?string $class
     */
    public function __construct(array $items = [], ?string $discrim = 'id', ?string $class = null)
    {
        $this->items = $items;
        $this->discrim = $discrim;
        $this->class = $class;
    }

    /**
     * Creates a collection from an array.
     *
     * @param array   $items
     * @param ?string $discrim
     * @param ?string $class
     *
     * @return ExCollectionInterface
     */
    public static function from(array $items = [], ?string $discrim = 'id', ?string $class = null)
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
}
