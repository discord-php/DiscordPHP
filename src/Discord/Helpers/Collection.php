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
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        if (array_key_exists('id', $value)) {
            if (! is_array($value)) {
                $this->items[$value->id] = $value;
            } else {
                $this->items[$value['id']] = $value;
            }

            return;
        }

        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }
}
