<?php

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