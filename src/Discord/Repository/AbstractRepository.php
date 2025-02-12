<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Helpers\Collection;

/**
 * Repositories provide a way to store and update parts on the Discord server.
 *
 * @since 4.0.0
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author David Cole <david.cole1340@gmail.com>
 *
 * @property      string       $discrim The discriminator.
 * @property-read CacheWrapper $cache   The react/cache wrapper.
 */
abstract class AbstractRepository extends Collection implements AbstractRepositoryInterface
{
    /**
     * The collection discriminator.
     *
     * @var string Discriminator.
     */
    protected $discrim = 'id';

    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Class type allowed into the collection.
     *
     * @var string
     */
    protected $class;

    use AbstractRepositoryTrait;
}
