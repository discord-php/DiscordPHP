<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    /**
     * Get an item from the collection with a
     * key and index.
     *
     * @param mixed $key
     * @param mixed $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $value = null, $default = null)
    {
        foreach ($this->items as $item) {
            if (is_callable([$item, 'getAttribute'])) {
                if (! empty($item->getAttribute($key))) {
                    if ($item[$key] == $value) {
                        return $item;
                    }
                }
            } else {
                if (isset($item[$key])) {
                    if ($item[$key] == $value) {
                        return $item;
                    }
                }
            }
        }

        return $default;
    }
}
