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

use Discord\Parts\Part;

interface RepositoryInterface
{
    /**
     * Builds a new, empty part.
     *
     * @param array $attributes The attributes for the new part.
     * 
     * @return Part The new part.
     */
    public function new(array $attributes = []);

    /**
     * Attempts to get an object from the cache.
     *
     * @param string $key   The key to search for.
     * @param mixed  $value The value to match with the key.
     *
     * @return \React\Promise\Promise
     */
    public function get($key, $value);

    /**
     * Attempts to save a part to the Discord servers.
     *
     * @param Part $part The part to save.
     *
     * @return \React\Promise\Promise
     */
    public function save(Part &$part);

    /**
     * Attempts to delete a part on the Discord servers.
     *
     * @param Part $part The part to delete.
     *
     * @return \React\Promise\Promise
     */
    public function delete(Part &$part);

    /**
     * Returns a part with fresh values.
     *
     * @param Part $part The part to get fresh values.
     *
     * @return \React\Promise\Promise
     */
    public function fresh(Part &$part);

    /**
     * Force gets a part from the Discord servers.
     *
     * @param string $id The ID to search for.
     *
     * @return \React\Promise\Promise
     */
    public function fetch($id);
}
