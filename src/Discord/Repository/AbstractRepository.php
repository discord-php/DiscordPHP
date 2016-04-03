<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Model\AbstractModel;
use Discord\Model\IdentifierModelInterface;
use Discord\Wrapper\CacheWrapper;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class AbstractRepository
{
    /**
     * @var CacheWrapper
     */
    protected $cache;

    /**
     * AbstractRepository constructor.
     *
     * @param CacheWrapper $cache
     */
    public function __construct(CacheWrapper $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param AbstractModel $model
     *
     * @return bool
     */
    public function add(AbstractModel $model)
    {
        $items = $this->all();

        $items[$this->getIdentifier($model)] = $model;

        $this->cache->set($this->getKey(), $items);

        return true;
    }

    /**
     * @param AbstractModel $model
     *
     * @return bool
     */
    public function delete(AbstractModel $model)
    {
        $items = $this->all();

        unset($items[$this->getIdentifier($model)]);

        $this->cache->set($this->getKey(), $items);

        return true;
    }

    /**
     * @param AbstractModel $model
     *
     * @return bool
     */
    public function has(AbstractModel $model)
    {
        return $this->hasKey($this->getIdentifier($model));
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function hasKey($identifier)
    {
        return array_key_exists($identifier, $this->all());
    }

    public function get($identifier)
    {
        return $this->all()[$identifier];
    }

    /**
     * @param AbstractModel $model
     *
     * @return bool
     */
    public function update(AbstractModel $model)
    {
        return $this->add($model);
    }

    /**
     * @return AbstractModel[]
     */
    public function all()
    {
        $items = $this->cache->get($this->getKey());
        if (null === $items) {
            $items = [];
        }

        return $items;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->all());
    }

    /**
     * @param AbstractModel $model
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getIdentifier(AbstractModel $model)
    {
        if ($model instanceof IdentifierModelInterface) {
            return $model->getId();
        }

        throw new \Exception('This Repository must override getIdentifier');
    }

    /**
     * @return string
     */
    abstract public function getModel();

    /**
     * @return string
     */
    protected function getKey()
    {
        return 'cache.repository.'.str_replace('\\', '-', $this->getModel());
    }
}
