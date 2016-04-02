<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts;

use ArrayAccess;
use Serializable;
use JsonSerializable;
use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Exceptions\PartRequestFailedException;
use Discord\Helpers\Guzzle;
use Illuminate\Support\Str;

/**
 * This class is the base of all objects that are returned. All "Parts" extend off this
 * base class.
 */
abstract class Part implements ArrayAccess, Serializable, JsonSerializable
{
    /**
     * The parts fillable attributes.
     *
     * @var array The array of attributes that can be mass-assigned.
     */
    protected $fillable = [];

    /**
     * Extra fillable defined by the base part.
     *
     * @var array Extra attributes that can be mass-assigned.
     */
    protected $extra_fillable = [];

    /**
     * The parts attributes.
     *
     * @var array The parts attributes and content.
     */
    protected $attributes = [];

    /**
     * The parts attributes cache.
     *
     * @var array Attributes which are cached such as parts that are retrieved over REST.
     */
    protected $attributes_cache = [];

    /**
     * Attributes that are hidden from debug info.
     *
     * @var array Attributes that are hidden from public.
     */
    protected $hidden = [];

    /**
     * URIs used to get/create/update/delete the part.
     *
     * @var array Contains URIs to modify the part.
     */
    protected $uris = [];

    /**
     * Is the part already created in the Discord servers?
     *
     * @var bool Whether the part has been created.
     */
    protected $created = false;

    /**
     * Is the part deleted in the Discord servers?
     *
     * @var bool Whether the part has been deleted.
     */
    protected $deleted = false;

    /**
     * The regex pattern to replace variables with.
     *
     * @var string The regex which is used to replace placeholders.
     */
    protected $regex = '/:([a-z_]+)/';

    /**
     * Is the part findable?
     *
     * @var bool Whether the part is findable.
     */
    public $findable = true;

    /**
     * Is the part creatable?
     *
     * @var bool Whether the part is creatable.
     */
    public $creatable = true;

    /**
     * Is the part deletable?
     *
     * @var bool Whether the part is deletable.
     */
    public $deletable = true;

    /**
     * Is the part editable?
     *
     * @var bool Whether the part is editable.
     */
    public $editable = true;

    /**
     * Should we fill the part after saving?
     *
     * @var bool Whether the part will be saved after being filled.
     */
    protected $fillAfterSave = true;

    /**
     * Create a new part instance.
     *
     * @param array $attributes An array of attributes to build the part.
     * @param bool  $created    Whether the part has already been created.
     *
     * @return void
     */
    public function __construct(array $attributes = [], $created = false)
    {
        $this->created = $created;
        $this->fill($attributes);

        if (is_callable([$this, 'afterConstruct'])) {
            $this->afterConstruct();
        }
    }

    /**
     * Attempts to get the part from the servers and
     * return it.
     *
     * @param string $id An ID to find a part with.
     *
     * @return Part|null Either a Part if it was found or null.
     */
    public static function find($id)
    {
        $part = new static([], true);

        if (! $part->findable) {
            return;
        }

        try {
            $request = Guzzle::get($part->uriReplace('get', ['id' => $id]));
        } catch (DiscordRequestFailedException $e) {
            return;
        }

        $part->fill($request);

        return $part;
    }

    /**
     * Fills the parts attributes from an array.
     *
     * @param array $attributes An array of attributes to build the part.
     *
     * @return void
     */
    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable + $this->extra_fillable)) {
                $this->setAttribute($key, $value);
            }
        }
    }

    /**
     * Gets a fresh copy of the part.
     *
     * @return bool Whether the attempt to get a fresh copy succeeded or failed.
     */
    public function fresh()
    {
        if ($this->deleted || ! $this->created) {
            return false;
        }

        $request = Guzzle::get($this->get);

        $this->fill($request);

        return true;
    }

    /**
     * Saves the part to the Discord servers.
     *
     * @return bool Whether the attempt to save the part succeeded or failed.
     */
    public function save()
    {
        $attributes = $this->created ? $this->getUpdatableAttributes() : $this->getCreatableAttributes();

        try {
            if ($this->created) {
                if (! $this->editable) {
                    return false;
                }

                $request = Guzzle::patch($this->replaceWithVariables($this->uris['update']), $attributes);
            } else {
                if (! $this->creatable) {
                    return false;
                }

                $request       = Guzzle::post($this->replaceWithVariables($this->uris['create']), $attributes);
                $this->created = true;
                $this->deleted = false;
            }
        } catch (\Exception $e) {
            throw new PartRequestFailedException($e->getMessage());
        }

        if ($this->fillAfterSave) {
            $this->fill($request);
        }

        return true;
    }

    /**
     * Deletes the part on the Discord servers.
     *
     * @return bool Whether the attempt to delete the part succeeded or failed.
     */
    public function delete()
    {
        if (! $this->deletable) {
            return false;
        }

        try {
            $request       = Guzzle::delete($this->replaceWithVariables($this->uris['delete']));
            $this->created = false;
            $this->deleted = true;
        } catch (\Exception $e) {
            throw new PartRequestFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * Clears the attribute cache.
     *
     * @return bool Whether the attempt to clear the cache succeeded or failed.
     */
    public function clearCache()
    {
        $this->attributes_cache = [];

        return true;
    }

    /**
     * Checks if there is a mutator present.
     *
     * @param string $key  The attribute name to check.
     * @param string $type Either get or set.
     *
     * @return mixed Either a string if it is callable or false.
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
     * Replaces variables in string with syntax :{varname}.
     *
     * @param string $string A string with placeholders.
     *
     * @return string A string with placeholders replaced.
     */
    public function replaceWithVariables($string)
    {
        $matches = null;
        $matcher = preg_match_all($this->regex, $string, $matches);

        $original = $matches[0];
        $vars     = $matches[1];

        foreach ($vars as $key => $variable) {
            if ($attribute = $this->getAttribute($variable)) {
                $string = str_replace($original[$key], $attribute, $string);
            }
        }

        return $string;
    }

    /**
     * Replaces variables in one of the URIs.
     *
     * @param string $key    A key from URIs.
     * @param array  $params Parameters to replace placeholders with.
     *
     * @return string A string with placeholders replaced.
     *
     * @see self::$uris The URIs that can be replaced.
     */
    public function uriReplace($key, $params)
    {
        $string = $this->uris[$key];

        $matches = null;
        $matcher = preg_match_all($this->regex, $string, $matches);

        $original = $matches[0];
        $vars     = $matches[1];

        foreach ($vars as $key => $variable) {
            if ($attribute = $params[$variable]) {
                $string = str_replace($original[$key], $attribute, $string);
            }
        }

        return $string;
    }

    /**
     * Gets an attribute on the part.
     *
     * @param string $key The key to the attribute.
     *
     * @return mixed Either the attribute if it exists or void.
     */
    public function getAttribute($key)
    {
        if ($str = $this->checkForMutator($key, 'get')) {
            return $this->{$str}();
        }

        if (! isset($this->attributes[$key])) {
            return;
        }

        return $this->attributes[$key];
    }

    /**
     * Sets an attribute on the part.
     *
     * @param string $key   The key to the attribute.
     * @param mixed  $value The value of the attribute.
     *
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
     * Sets a cache attribute on the part.
     *
     * @param string $key   The cache key.
     * @param mixed  $value The cache value.
     *
     * @return void
     */
    public function setCache($key, $value)
    {
        $this->attributes_cache[$key] = $value;
    }

    /**
     * Checks if the cache has a specific key.
     *
     * @param string $key The key to check for.
     *
     * @return bool Whether the cache has the key.
     */
    public function cacheHas($key)
    {
        return isset($this->attributes_cache[$key]);
    }

    /**
     * Gets an attribute via key. Used for ArrayAccess.
     *
     * @param string $key The attribute key.
     *
     * @return mixed
     *
     * @see self::getAttribute() This function forwards onto getAttribute.
     */
    public function offsetGet($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Checks if an attribute exists via key. Used for ArrayAccess.
     *
     * @param string $key The attribute key.
     *
     * @return bool Whether the offset exists.
     */
    public function offsetExists($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Sets an attribute via key. Used for ArrayAccess.
     *
     * @param string $key   The attribute key.
     * @param mixed  $value The attribute value.
     *
     * @return void
     *
     * @see self::setAttribute() This function forwards onto setAttribute.
     */
    public function offsetSet($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Unsets an attribute via key. Used for ArrayAccess.
     *
     * @param string $key The attribute key.
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        if (isset($this->attributes[$key])) {
            unset($this->attributes[$key]);
        }
    }

    /**
     * Serializes the data. Used for Serializable.
     *
     * @return mixed A string of serialized data.
     */
    public function serialize()
    {
        return serialize($this->attributes);
    }

    /**
     * Unserializes some data and stores it. Used for Serializable.
     *
     * @param mixed $data Some serialized data.
     *
     * @return mixed Unserialized data.
     *
     * @see self::setAttribute() The unserialized data is stored with setAttribute.
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        foreach ($data as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * Provides data when the part is encoded into
     * JSON. Used for JsonSerializable.
     *
     * @return array An array of public attributes.
     *
     * @see self::getPublicAttributes() This function forwards onto getPublicAttributes.
     */
    public function jsonSerialize()
    {
        return $this->getPublicAttributes();
    }

    /**
     * Returns an array of public attributes.
     *
     * @return array An array of public attributes.
     */
    public function getPublicAttributes()
    {
        $data = [];

        foreach ($this->attributes as $key => $value) {
            if (in_array($key, $this->hidden)) {
                continue;
            }

            $data[$key] = $this->getAttribute($key);
        }

        return $data;
    }

    /**
     * Converts the part to a string.
     *
     * @return string A JSON string of attributes.
     *
     * @see self::getPublicAttributes() This function encodes getPublicAttributes into JSON.
     */
    public function __toString()
    {
        return json_encode($this->getPublicAttributes());
    }

    /**
     * Handles debug calls from var_dump and similar functions.
     *
     * @return array An array of public attributes.
     *
     * @see self::getPublicAttributes() This function forwards onto getPublicAttributes.
     */
    public function __debugInfo()
    {
        return $this->getPublicAttributes();
    }

    /**
     * Handles dynamic get calls onto the part.
     *
     * @param string $key The attributes key.
     *
     * @return mixed The value of the attribute.
     *
     * @see self::getAttribute() This function forwards onto getAttribute.
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Handles dynamic set calls onto the part.
     *
     * @param string $key   The attributes key.
     * @param mixed  $value The attributes value.
     *
     * @return void
     *
     * @see self::setAttribute() This function forwards onto setAttribute.
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }
}
