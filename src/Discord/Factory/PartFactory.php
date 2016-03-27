<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Factory;

use Discord\Guzzle;
use Discord\Parts;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class PartFactory
{
    /**
     * @var Guzzle
     */
    private $guzzle;

    /**
     * PartFactory constructor.
     *
     * @param Guzzle $guzzle
     */
    public function __construct(Guzzle $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * @param string       $type       The type of part to create
     * @param array|object $attributes An array of attributes to build the part.
     * @param bool         $created    Whether the part has already been created.x
     *
     * @return Parts\Part
     */
    public function create($type, $attributes = [], $created = false)
    {
        if (!is_array($attributes)) {
            $attributes = (array) $attributes;
        }

        $part = new $type($this, $this->guzzle, $attributes, $created);

        return $part;
    }
}
