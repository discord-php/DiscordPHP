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
    protected $discrim = 'id';

    /**
     * {@inheritdoc}
     *
     * @param string $discrim The discriminator.
     */
    public function __construct($items = [], $discrim = null)
    {
        $this->discrim = $discrim;

        parent::__construct($items);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        if (! is_null($this->discrim)) {
            if (! is_array($value)) {
                $this->items[$value->{$this->discrim}] = $value;
            } else {
                $this->items[$value[$this->discrim]] = $value;
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
