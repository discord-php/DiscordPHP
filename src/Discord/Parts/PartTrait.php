<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts;

use Carbon\Carbon;
use Discord\Discord;
use Discord\Factory\Factory;
use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Http\Http;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * @property Http    $http               The HTTP client.
 * @property Factory $factory            The factory instance.
 * @property Discord $discord            The Discord client.
 * @property array   $scriptData         Custom script data. Used for storing custom information, used by end products.
 * @property array   $fillable           The array of attributes that can be mass-assigned.
 * @property array   $attributes         The parts attributes and content.
 * @property array   $visible            Attributes which are visible from debug info.
 * @property array   $hidden             Attributes that are hidden from public.
 * @property array   $repositories       Repositories that can exist in a part.
 * @property array   $repositories_cache An array of repositories.
 * @property bool    $created            Whether the part has been created.
 */
trait PartTrait
{
    /**
     * Called after the part has been constructed.
     */
    protected function afterConstruct(): void
    {
    }

    /**
     * Save the part with its originating repository.
     *
     * @param string|null $reason The reason for the audit log, if supported.
     *
     * @throws \Exception             If the part does not support saving.
     * @throws NoPermissionsException Missing permission.
     *
     * @return PromiseInterface<Part> Resolves with the saved part.
     */
    public function save(?string $reason = null): PromiseInterface
    {
        return reject(new \Exception('This part does not support saving.'));
    }

    /**
     * Whether the part is considered partial i.e. missing information which can
     * be fetched from Discord.
     *
     * @return bool
     */
    public function isPartial(): bool
    {
        return false;
    }

    /**
     * Fetches any missing information about the part from Discord's servers.
     *
     * @throws \RuntimeException The part is not fetchable.
     *
     * @return PromiseInterface<static>
     */
    public function fetch(): PromiseInterface
    {
        throw new \RuntimeException('This part is not fetchable.');
    }

    /**
     * Fills the parts attributes from an array.
     *
     * @param array $attributes An array of attributes to build the part.
     *
     * @see self::setAttribute()
     */
    public function fill(array $attributes): void
    {
        foreach ($this->fillable as $key) {
            if (array_key_exists($key, $attributes)) {
                // This is like setAttribute() but without in_array() checks on fillable
                if ($str = $this->checkForSetMutator($key)) {
                    $this->{$str}($attributes[$key]);
                } else {
                    $this->attributes[$key] = $attributes[$key];
                }
            }
        }
    }

    /**
     * Checks if there is a get mutator present.
     *
     * @param string $key The attribute name to check.
     *
     * @since 10.0.0 Replaces checkForMutator($key, 'get')
     *
     * @return string|false Either a string if it is a method or false.
     */
    protected function checkForGetMutator(string $key)
    {
        $str = 'get'.self::studly($key).'Attribute';

        if (method_exists($this, $str)) {
            return $str;
        }

        return false;
    }

    /**
     * Checks if there is a set mutator present.
     *
     * @param string $key The attribute name to check.
     *
     * @since 10.0.0 Replaces checkForMutator($key, 'set')
     *
     * @return string|false Either a string if it is a method or false.
     */
    protected function checkForSetMutator(string $key)
    {
        $str = 'set'.self::studly($key).'Attribute';

        if (method_exists($this, $str)) {
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
    protected function getAttribute(string $key)
    {
        if (isset($this->repositories[$key])) {
            if (! isset($this->repositories_cache[$key])) {
                $this->repositories_cache[$key] = $this->factory->repository($this->repositories[$key], $this->getRepositoryAttributes());
            }

            return $this->repositories_cache[$key];
        }

        if ($str = $this->checkForGetMutator($key)) {
            return $this->{$str}();
        }

        if (! isset($this->attributes[$key])) {
            return null;
        }

        return $this->attributes[$key];
    }

    /**
     * Sets an attribute on the part.
     *
     * @param string $key   The key to the attribute.
     * @param mixed  $value The value of the attribute.
     */
    protected function setAttribute(string $key, $value): void
    {
        if ($str = $this->checkForSetMutator($key)) {
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
     * @see Part::getAttribute() This function forwards onto getAttribute.
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
     * @see Part::setAttribute() This function forwards onto setAttribute.
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
        unset($this->attributes[$key]);
    }

    /**
     * Serializes the data. Used for Serializable.
     *
     * @return ?string A string of serialized data.
     */
    public function serialize(): ?string
    {
        return serialize($this->getRawAttributes());
    }

    public function __serialize(): array
    {
        return $this->getRawAttributes();
    }

    /**
     * Unserializes some data and stores it. Used for Serializable.
     *
     * @param string $data Some serialized data.
     *
     * @see Part::setAttribute() The unserialized data is stored with setAttribute.
     */
    public function unserialize($data): void
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
     * @see Part::getPublicAttributes() This function forwards onto getPublicAttributes.
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
     * Note: The order matters for repository tree (top to bottom).
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
     * Return key-value attributes if it has been filled.
     *
     * To be used with fields marked "optional?" from the API.
     *
     * @param array $attributes Names of optional attribute
     *
     * @return array
     */
    protected function makeOptionalAttributes(array $attributes): array
    {
        $attr = [];
        foreach ($attributes as $key => $value) {
            if (array_key_exists($key, $this->attributes)) {
                $attr[$key] = $value;
            } elseif (is_int($key) && array_key_exists($value, $this->attributes)) {
                $attr[$value] = $this->attributes[$value];
            }
        }

        return $attr;
    }

    /**
     * Get the Discord instance that owns this Part.
     *
     * @return Discord
     */
    public function getDiscord(): Discord
    {
        return $this->discord;
    }

    /**
     * Create a Part where the `created` status is referenced by this Part.
     *
     * @internal
     *
     * @see \Discord\Factory\Factory::part()
     *
     * @since 10.0.0
     *
     * @param string       $class The attribute Part class to build.
     * @param array|object $data  Data to create the object.
     *
     * @return Part
     */
    public function createOf(string $class, array|object $data): self
    {
        $ofPart = $this->factory->part($class, (array) $data, $this->created);
        $ofPart->created = &$this->created;

        return $ofPart;
    }

    /**
     * Converts a string to studlyCase.
     *
     * This is a port of updated Laravel's implementation, a non-regex with
     * static cache. The Discord\studly() is kept due to unintended bug and we
     * do not want to introduce BC by replacing it. This method is private
     * static as we may move it outside this class in future.
     *
     * @param string $string The string to convert.
     *
     * @return string
     *
     * @since 10.0.0
     */
    private static function studly(string $string): string
    {
        static $studlyCache = [];

        if (isset($studlyCache[$string])) {
            return $studlyCache[$string];
        }

        $words = explode(' ', str_replace(['-', '_'], ' ', $string));

        $studlyWords = array_map('ucfirst', $words);

        return $studlyCache[$string] = implode($studlyWords);
    }

    /**
     * Helps with getting ISO8601 timestamp attributes.
     *
     * @param string $key   The attribute key.
     * @param string $class The attribute class.
     *
     * @throws \Exception
     *
     * @return Carbon|null
     *
     * @since 10.19.0
     */
    protected function attributeCarbonHelper($key): ?Carbon
    {
        if (! isset($this->attributes[$key])) {
            return null;
        }

        return ($this->attributes[$key] instanceof Carbon)
            ? $this->attributes[$key]
            : $this->attributes[$key] = Carbon::parse($this->attributes[$key]);
    }

    /**
     * Helps with getting Part attributes.
     *
     * @param string  $key     The attribute key.
     * @param string  $class   The attribute class.
     * @param ?string $discrim The attribute discriminator.
     *
     * @throws \Exception
     *
     * @return ExCollectionInterface
     *
     * @since 10.19.0
     */
    protected function attributeCollectionHelper($key, $class, ?string $discrim = 'id'): ExCollectionInterface
    {
        $collection = Collection::for($class, $discrim);

        if (empty($this->attributes[$key])) {
            return $collection;
        }

        foreach ($this->attributes[$key] as &$part) {
            $collection->pushItem(
                $part instanceof $class
                    ? $part
                    : $part = $this->createOf($class, $part)
            );
        }

        return $collection;
    }

    /**
     * Helps with getting Part attributes for classes with extended types.
     *
     * @param string $class The attribute class.
     * @param string $key   The attribute key.
     *
     * @return ExCollectionInterface
     */
    protected function attributeTypedCollectionHelper(string $class, string $key): ExCollectionInterface
    {
        $collection = Collection::for($class);

        if (empty($this->attributes[$key])) {
            return $collection;
        }

        foreach ($this->attributes[$key] as &$part) {
            if (! $part instanceof $class) {
                $part = $this->createOf($class::TYPES[$part->type ?? 0], $part);
            }
            $collection->pushItem($part);
        }

        return $collection;
    }

    /**
     * Helps with getting Part attributes.
     *
     * @param string $key       The attribute key.
     * @param string $class     The attribute class.
     * @param array  $extraData Extra data to pass to the part constructor.
     *
     * @throws \Exception
     *
     * @return Part|null
     *
     * @since 10.19.0
     */
    protected function attributePartHelper($key, $class, $extraData = []): ?Part
    {
        if (! isset($this->attributes[$key]) || ! $this->attributes[$key]) {
            return null;
        }

        return ($this->attributes[$key] instanceof $class)
            ? $this->attributes[$key]
            : $this->attributes[$key] = $this->createOf($class, ((array) $this->attributes[$key]) + $extraData);
    }

    /**
     * Returns an array of constant names and their values.
     *
     * @return array An associative array where keys are constant names and values are their values.
     *
     * @since 10.19.0
     */
    public function getConstants(): array
    {
        $reflection = new \ReflectionClass($this::class);

        return $reflection->getConstants();
    }

    /**
     * Converts the part to a string.
     *
     * @return string A JSON string of attributes.
     *
     * @throws \Exception
     * @see Part::getPublicAttributes() This function encodes getPublicAttributes into JSON.
     */
    public function __toString(): string
    {
        return json_encode($this->getPublicAttributes());
    }

    /**
     * Handles debug calls from var_dump and similar functions.
     *
     * @return array An array of public attributes.
     *
     * @throws \Exception
     * @see Part::getPublicAttributes() This function forwards onto getPublicAttributes.
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
     * @see Part::getAttribute() This function forwards onto getAttribute.
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
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }
}
