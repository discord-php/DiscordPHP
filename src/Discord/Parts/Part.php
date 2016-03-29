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
use Discord\Exceptions\PartRequestFailedException;
use Discord\Factory\PartFactory;
use Discord\Http\Http;
use Discord\Wrapper\CacheWrapper;
use Illuminate\Support\Str;
use JsonSerializable;
use React\Promise\Deferred;
use Serializable;

/**
 * This class is the base of all objects that are returned. All "Parts" extend off this
 * base class.
 */
abstract class Part implements ArrayAccess, Serializable, JsonSerializable
{
    /**
     * @var PartFactory
     */
    protected $partFactory;

    /**
     * @var Http
     */
    protected $http;

    /**
     * @var CacheWrapper
     */
    protected $cache;

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
     * The promise resolve function.
     *
     * @var \Closure Resolve function.
     */
    public $resolve;

    /**
     * The promise reject function.
     *
     * @var \Closure Reject function.
     */
    public $reject;

    /**
     * Create a new part instance.
     *
     * @param PartFactory  $partFactory
     * @param Http         $http
     * @param CacheWrapper $cache
     * @param array        $attributes An array of attributes to build the part.
     * @param bool         $created    Whether the part has already been created.
     */
    public function __construct(
        PartFactory $partFactory,
        Http $http,
        CacheWrapper $cache,
        array $attributes = [],
        $created = false
    ) {
        $this->partFactory = $partFactory;
        $this->http        = $http;
        $this->cache       = $cache;

        $this->created = $created;
        $this->fill($attributes);

        if (is_callable([$this, 'afterConstruct'])) {
            $this->afterConstruct();
        }

        $this->resolve = function ($response, &$deferred) {
            $deferred->resolve(true);
        };

        $this->reject = function ($e, &$deferred) {
            $deferred->reject($e);
        };
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
            return \React\Promise\reject(new \Exception('You cannot get a non-existant part.'));
        }

        $deferred = new Deferred();

        $this->http->get($this->get)->then(
            function ($response) {
                $this->fill($response);
            },
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Saves the part to the Discord servers.
     *
     * @throws PartRequestFailedException
     *
     * @return bool Whether the attempt to save the part succeeded or failed.
     */
    public function save()
    {
        $deferred = new Deferred();

        $attributes = $this->created ? $this->getUpdatableAttributes() : $this->getCreatableAttributes();

        if ($this->created) {
            if (! $this->editable) {
                return \React\Promise\reject(new \Exception('You cannot edit a non-editable part.'));
            }

            $this->http->post(
                $this->replaceWithVariables($this->uris['update']),
                $attributes
            )->then(
                function () use ($deferred) {
                    $deferred->resolve(true);
                },
                \React\Partial\bind_right($this->reject, $deferred)
            );
        } else {
            if (! $this->creatable) {
                return \React\Promise\reject(new \Exception('You cannot create a non-creatable part.'));
            }

            $this->http->post(
                $this->replaceWithVariables($this->uris['create']),
                $attributes
            )->then(
                function () use ($deferred) {
                    $this->created = true;
                    $this->deleted = false;

                    $deferred->resolve(true);
                },
                \React\Partial\bind_right($this->reject, $deferred)
            );
        }

        if ($this->fillAfterSave) {
            $this->fill($request);
        }

        return $deferred->promise();
    }

    /**
     * Deletes the part on the Discord servers.
     *
     * @throws PartRequestFailedException
     *
     * @return \React\Promise\Promise Whether the attempt to delete the part succeeded or failed.
     */
    public function delete()
    {
        if (! $this->deletable) {
            return \React\Promise\reject(new \Exception('You cannot delete a non-deletable part.'));
        }

        $deferred = new Deferred();

        $this->http->delete(
            $this->replaceWithVariables($this->uris['delete'])
        )->then(
            function () use ($deferred) {
                $this->created = false;
                $this->deleted = true;

                $deferred->resolve(true);
            },
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
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
