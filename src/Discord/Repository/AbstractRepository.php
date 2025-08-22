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

namespace Discord\Repository;

use Discord\Discord;
use Discord\Helpers\CacheWrapper;
use Discord\Helpers\Collection;
use Discord\Helpers\LegacyCacheWrapper;

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
    use AbstractRepositoryTrait;
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

    /**
     * AbstractRepository constructor.
     *
     * @param Discord $discord
     * @param array   $vars    An array of variables used for the endpoint.
     */
    public function __construct(protected $discord, array $vars = [])
    {
        $this->http = $discord->getHttpClient();
        $this->factory = $discord->getFactory();
        $this->vars = $vars;
        if ($cacheConfig = $discord->getCacheConfig(static::class)) {
            $this->cache = new CacheWrapper($discord, $cacheConfig, $this->items, $this->class, $this->vars);
        } else {
            $this->cache = new LegacyCacheWrapper($discord, $this->items, $this->class);
        }
    }

    /** @return array */
    public function __debugInfo(): array
    {
        $vars = get_object_vars($this);
        $vars['class'] = $this::class;
        unset(
            $vars['http'],
            $vars['factory'],
            $vars['discord'],
            $vars['visible'],
            $vars['hidden'],
            $vars['repositories'],
            $vars['repositories_cache'],
            $vars['fillable'],
            $vars['scriptData']
        );

        return $vars;
    }
}
