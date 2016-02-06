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

/**
 * Collections are the 'arrays' that we use. They are extended from
 * Laravel collections.
 *
 * @see https://laravel.com/docs/5.2/collections In depth documentation can be found on the Laravel website.
 */
class Collection extends BaseCollection
{
    /**
     * Get an item from the collection with a
     * key and index.
     *
     * @param mixed $key     The key that we will match with the name.
     * @param mixed $name    The name that we will match with the key.
     * @param mixed $default Returned if we can't find the part.
     *
     * @return mixed An object in the collection or $default.
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
