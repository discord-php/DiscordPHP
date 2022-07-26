<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts;

use ArrayAccess;
use Carbon\Carbon;
use Discord\Discord;
use Discord\Factory\Factory;
use Discord\Http\Http;
use JsonSerializable;
use React\Promise\ExtendedPromiseInterface;

use function Discord\studly;

/**
 * This class is the base of all objects that are returned. All "Parts" extend off this
 * base class.
 */
abstract class Part implements ArrayAccess, JsonSerializable
{
    /**
     * The HTTP client.
     *
     * @var Http Client.
     */
    protected $http;

    /**
     * The factory.
     *
     * @var Factory Factory.
     */
    protected $factory;

    /**
     * The Discord client.
     *
     * @var Discord Client.
     */
    protected $discord;

    /**
     * Custom script data.
     * Used for storing custom information, used by end products.
     *
     * @var mixed
     */
    public $scriptData;

    /**
     * The parts fillable attributes.
     *
     * @var array The array of attributes that can be mass-assigned.
     */
    protected $fillable = [];

    /**
     * The parts attributes.
     *
     * @var array The parts attributes and content.
     */
    protected $attributes = [];

    /**
     * Attributes which are visible from debug info.
     *
     * @var array
     */
    protected $visible = [];

    /**
     * Attributes that are hidden from debug info.
     *
     * @var array Attributes that are hidden from public.
     */
    protected $hidden = [];

    /**
     * An array of repositories that can exist in a part.
     *
     * @var array Repositories.
     */
    protected $repositories = [];

    /**
     * An array of repositories.
     *
     * @var array
     */
    protected $repositories_cache = [];

    /**
     * Is the part already created in the Discord servers?
     *
     * @var bool Whether the part has been created.
     */
    public $created = false;

    /**
     * The regex pattern to replace variables with.
     *
     * @var string The regex which is used to replace placeholders.
     */
    protected $regex = '/:([a-z_]+)/';

    /**
     * Should we fill the part after saving?
     *
     * @var bool Whether the part will be saved after being filled.
     */
    protected $fillAfterSave = true;

    /**
     * Create a new part instance.
     *
     * @param Discord $discord    The Discord client.
     * @param array   $attributes An array of attributes to build the part.
     * @param bool    $created    Whether the part has already been created.
     */
    public function __construct(Discord $discord, array $attributes = [], bool $created = false)
    {
        $this->discord = $discord;
        $this->factory = $discord->getFactory();
        $this->http = $discord->getHttpClient();

        $this->created = $created;
        $this->fill($attributes);

        $this->afterConstruct();
    }

    /**
     * Called after the part has been constructed.
     */
    protected function afterConstruct(): void
    {
    }

    /**
     * Whether the part is considered partial
     * i.e. missing information which can be
     * fetched from Discord.
     *
     * @return bool
     */
    public function isPartial(): bool
    {
        return false;
    }

    /**
     * Fetches any missing information about
     * the part from Discord's servers.
     *
     * @throws \RuntimeException The part is not fetchable.
     *
     * @return ExtendedPromiseInterface<static>
     */
    public function fetch(): ExtendedPromiseInterface
    {
        throw new \RuntimeException('This part is not fetchable.');
    }

    /**
     * Fills the parts attributes from an array.
     *
     * @param array $attributes An array of attributes to build the part.
     */
    public function fill(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->setAttribute($key, $value);
            }
        }
    }

    /**
     * Checks if there is a mutator present.
     *
     * @param string $key  The attribute name to check.
     * @param string $type Either get or set.
     *
     * @return string|false Either a string if it is callable or false.
     */
    private function checkForMutator(string $key, string $type)
    {
        $str = $type.studly($key).'Attribute';

        if (is_callable([$this, $str])) {
            return $str;
        }

        return false;
    }

    /**
     * Gets an attribute on the part.
     *
     * @param string $key The key to the attribute.
     *
     * @return mixed      Either the attribute if it exists or void.
     * @throws \Exception
     */
    private function getAttribute(string $key)
    {
        if (isset($this->repositories[$key])) {
            if (! isset($this->repositories_cache[$key])) {
                $this->repositories_cache[$key] = $this->factory->create($this->repositories[$key], $this->getRepositoryAttributes());
            }

            return $this->repositories_cache[$key];
        }

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
     */
    private function setAttribute(string $key, $value): void
    {
        if ($str = $this->checkForMutator($key, 'set')) {
            $this->{$str}($value);

            return;
        }

        if (in_array($key, $this->fillable)) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Gets an attribute via key. Used for ArrayAccess.
     *
     * @param string $key The attribute key.
     *
     * @return mixed
     *
     * @throws \Exception
     * @see self::getAttribute() This function forwards onto getAttribute.
     */
    #[\ReturnTypeWillChange]
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
    public function offsetExists($key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Sets an attribute via key. Used for ArrayAccess.
     *
     * @param string $key   The attribute key.
     * @param mixed  $value The attribute value.
     *
     *
     * @see self::setAttribute() This function forwards onto setAttribute.
     */
    public function offsetSet($key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Unsets an attribute via key. Used for ArrayAccess.
     *
     * @param string $key The attribute key.
     */
    public function offsetUnset($key): void
    {
        if (isset($this->attributes[$key])) {
            unset($this->attributes[$key]);
        }
    }

    /**
     * Serializes the data. Used for Serializable.
     *
     * @return string A string of serialized data.
     */
    public function serialize()
    {
        return serialize($this->attributes);
    }

    public function __serialize(): array
    {
        return $this->attributes;
    }

    /**
     * Unserializes some data and stores it. Used for Serializable.
     *
     * @param string $data Some serialized data.
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
    
    public function __unserialize(array $data): void
    {
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
     * @throws \Exception
     * @see self::getPublicAttributes() This function forwards onto getPublicAttributes.
     */
    public function jsonSerialize(): array
    {
        return $this->getPublicAttributes();
    }

    /**
     * Returns an array of public attributes.
     *
     * @return array      An array of public attributes.
     * @throws \Exception
     */
    public function getPublicAttributes(): array
    {
        $data = [];

        foreach (array_merge($this->fillable, $this->visible) as $key) {
            if (in_array($key, $this->hidden)) {
                continue;
            }

            $value = $this->getAttribute($key);

            if ($value instanceof Carbon) {
                $value = $value->format('Y-m-d\TH:i:s\Z');
            }

            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Returns an array of raw attributes.
     *
     * @return array Raw attributes.
     */
    public function getRawAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Gets the attributes to pass to repositories.
     *
     * @return array Attributes.
     */
    public function getRepositoryAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Returns the attributes needed to create.
     *
     * @return array
     */
    public function getCreatableAttributes(): array
    {
        return [];
    }

    /**
     * Returns the updatable attributes.
     *
     * @return array
     */
    public function getUpdatableAttributes(): array
    {
        return [];
    }

    /**
     * Converts the part to a string.
     *
     * @return string A JSON string of attributes.
     *
     * @throws \Exception
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
     * @throws \Exception
     * @see self::getPublicAttributes() This function forwards onto getPublicAttributes.
     */
    public function __debugInfo(): array
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
     * @throws \Exception
     * @see self::getAttribute() This function forwards onto getAttribute.
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Handles dynamic set calls onto the part.
     *
     * @param string $key   The attributes key.
     * @param mixed  $value The attributes value.
     *
     * @see self::setAttribute() This function forwards onto setAttribute.
     */
    public function __set(string $key, $value)
    {
        $this->setAttribute($key, $value);
    }
}
