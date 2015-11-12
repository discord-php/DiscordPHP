<?php

namespace Discord\Parts;

use Illuminate\Support\Str;

abstract class Part implements \ArrayAccess, \Serializable
{
	/**
	 * The parts attributes.
	 * 
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Attributes that are hidden from debug info.
	 *
	 * @var array 
	 */
	protected $hidden = ['guzzle'];

	/**
	 * Create a new part instance.
	 * 
	 * @param array $attributes
	 * @return void 
	 */
	public function __construct(array $attributes = [])
	{
		$this->fill($attributes);

		if (is_callable([$this, 'afterConstruct'])) {
			$this->afterConstruct();
		}
	}

	/**
	 * Fills the parts attributes from an array.
	 *
	 * @param array $attributes
	 * @return void 
	 */
	public function fill(array $attributes)
	{
		foreach ($attributes as $key => $value) {
			$this->setAttribute($key, $value);
		}
	}

	/**
	 * Checks if there is a mutator present.
	 *
	 * @param string $key 
	 * @param string $type
	 * @return mixed 
	 */
	public function checkForMutator($key, $type)
	{
		$str = $type.Str::studly($key).'Attribute';

		if (is_callable([$this, $str])) {
			return $str;
		}

		return false;
	}

	/**
	 * Gets an attribute on the part.
	 *
	 * @param string $key 
	 * @return mixed 
	 */
	public function getAttribute($key)
	{
		if ($str = $this->checkForMutator($key, 'get')) {
			return $this->{$str}();
		}

		if (!isset($this->attributes[$key])) {
			return null;
		}

		return $this->attributes[$key];
	}

	/**
	 * Sets an attribute on the part.
	 *
	 * @param string $key 
	 * @param mixed $value 
	 * @return void 
	 */
	public function setAttribute($key, $value)
	{
		if ($str = $this->checkForMutator($key, 'set')) {
			$this->{$str}($value);
			return;
		}

		$this->attributes[$key] = $value;
	}

	/**
	 * Gets an attribute via key. Used for ArrayAccess.
	 *
	 * @param string $key
	 * @return mixed 
	 */
	public function offsetGet($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * Checks if an attribute exists via key. Used for ArrayAccess.
	 *
	 * @param string $key 
	 * @return mixed 
	 */
	public function offsetExists($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Sets an attribute via key. Used for ArrayAccess.
	 *
	 * @param string $key 
	 * @param mixed $value 
	 * @return void 
	 */
	public function offsetSet($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	/**
	 * Unsets an attribute via key. Used for ArrayAccess.
	 *
	 * @param string $key 
	 * @return void 
	 */
	public function offsetUnset($key)
	{
		if (isset($this->attributes[$key])) {
			unset($this->attributes[$key]);
		}
	}

	/**
	 * Serializes the data. Used for \Serializable.
	 *
	 * @return mixed 
	 */
	public function serialize()
	{
		return serialize($this->attributes);
	}

	/**
	 * Unserializes some data. Used for \Serializable.
	 *
	 * @param mixed $data 
	 * @return mixed 
	 */
	public function unserialize($data)
	{
		$data = unserialize($data);

		foreach ($data as $key => $value) {
			$this->setAttribute($key, $value);
		}
	}

	/**
	 * Returns an array of public attributes
	 *
	 * @return array 
	 */
	public function getPublicAttributes()
	{
		$data = [];

		foreach ($this->attributes as $key => $value) {
			if (in_array($this->hidden, $key)) continue;
			$data[$key] = $value;
		}

		return $data;
	}
	
	/**
	 * Converts the part to a string.
	 *
	 * @return string 
	 */
	public function __toString()
	{	
		return json_encode($this->getPublicAttributes());	
	}

	/**
	 * Handles debug calls from var_dump and similar functions.
	 *
	 * @return array 
	 */
	public function __debugInfo()
	{
		return $this->getPublicAttributes();
	}

	/**
	 * Handles dynamic get calls onto the part.
	 *
	 * @param string $key 
	 * @return mixed 
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * Handles dynamic set calls onto the part.
	 *
	 * @param string $key 
	 * @param mixed $value 
	 * @return void 
	 */
	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}
}