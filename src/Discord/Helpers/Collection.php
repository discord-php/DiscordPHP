<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

/**
 * Collection of items. Inspired by Laravel Collections.
 *
 * @since 5.0.0 No longer extends Laravel's BaseCollection
 * @since 4.0.0
 */
class Collection implements ExCollectionInterface
{
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

    use CollectionTrait;
}
