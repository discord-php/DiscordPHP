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

use Discord\Helpers\CacheWrapper;
use Discord\Helpers\CollectionInterface;
use Discord\Helpers\CollectionTrait;
use Discord\Helpers\LegacyCacheWrapper;
use Discord\Http\Endpoint;
use Discord\Http\Http;
use Discord\Parts\Part;
use React\Promise\PromiseInterface;
use Traversable;
use WeakReference;

use function Discord\nowait;
use function React\Promise\reject;
use function React\Promise\resolve;

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
abstract class AbstractRepository implements CollectionInterface
{
    use AbstractRepositoryTrait;
}
