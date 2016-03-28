<?php

/**
 * This file is part of DiscordPHP
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Repository;

use Discord\Factory\PartFactory;
use Discord\Guzzle;
use Discord\Wrapper\CacheWrapper;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class AbstractRepository
{
    /**
     * @var Guzzle
     */
    protected $guzzle;

    /**
     * @var CacheWrapper
     */
    protected $cache;

    /**
     * @var PartFactory
     */
    protected $partFactory;

    /**
     * AbstractRepository constructor.
     *
     * @param Guzzle       $guzzle
     * @param CacheWrapper $cache
     * @param PartFactory  $partFactory
     */
    public function __construct(Guzzle $guzzle, CacheWrapper $cache, PartFactory $partFactory)
    {
        $this->guzzle      = $guzzle;
        $this->cache       = $cache;
        $this->partFactory = $partFactory;
    }
}
