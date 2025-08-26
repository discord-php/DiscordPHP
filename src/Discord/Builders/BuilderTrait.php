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

namespace Discord\Builders;

/**
 * Trait providing helper methods for builder classes.
 *
 * @since 10.19.0
 */
trait BuilderTrait
{
    /**
     * Fills the builder properties from an array.
     *
     * @param array $properties An array of properties to build the part.
     *
     * @see self::setProperty()
     */
    public function fill(array $properties): void
    {
        foreach ($properties as $key => $value) {
            $this->setProperty($key, $value);
        }
    }

    /**
     * Checks if there is a get mutator present.
     *
     * @param string $key The property name to check.
     *
     * @since 10.0.0 Replaces checkForMutator($key, 'get')
     *
     * @return string|false Either a string if it is a method or false.
     */
    protected function checkForGetMutator(string $key)
    {
        $str = 'get'.self::studly($key);

        if (method_exists($this, $str)) {
            return $str;
        }

        return false;
    }

    /**
     * Checks if there is a set mutator present.
     *
     * @param string $key The property name to check.
     *
     * @since 10.0.0 Replaces checkForMutator($key, 'set')
     *
     * @return string|false Either a string if it is a method or false.
     */
    protected function checkForSetMutator(string $key)
    {
        $str = 'set'.self::studly($key);

        if (method_exists($this, $str)) {
            return $str;
        }

        return false;
    }

    /**
     * Gets an property on the part.
     *
     * @param string $key The key to the property.
     *
     * @return mixed      Either the property if it exists or void.
     * @throws \Exception
     */
    protected function getProperty(string $key)
    {
        if ($str = $this->checkForGetMutator($key)) {
            return $this->{$str}();
        }

        if (! isset($this->{$key})) {
            return null;
        }

        return $this->{$key};
    }

    /**
     * Sets an property on the part.
     *
     * @param string $key   The key to the property.
     * @param mixed  $value The value of the property.
     */
    protected function setProperty(string $key, $value): void
    {
        if ($str = $this->checkForSetMutator($key)) {
            $this->{$str}($value);

            return;
        }

        if (property_exists($this, $key)) {
            $this->{$key} = $value;
        }
    }

    /**
     * Gets an property via key. Used for ArrayAccess.
     *
     * @param string $key The property key.
     *
     * @return mixed
     *
     * @throws \Exception
     * @see Part::getProperty() This function forwards onto getProperty.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->getProperty($key);
    }

    /**
     * Checks if an property exists via key. Used for ArrayAccess.
     *
     * @param string $key The property key.
     *
     * @return bool Whether the offset exists.
     */
    public function offsetExists($key): bool
    {
        return isset($this->{$key});
    }

    /**
     * Sets an property via key. Used for ArrayAccess.
     *
     * @param string $key   The property key.
     * @param mixed  $value The property value.
     *
     * @see Part::setProperty() This function forwards onto setProperty.
     */
    public function offsetSet($key, $value): void
    {
        $this->setProperty($key, $value);
    }

    /**
     * Unsets an property via key. Used for ArrayAccess.
     *
     * @param string $key The property key.
     */
    public function offsetUnset($key): void
    {
        unset($this->{$key});
    }

    /**
     * Serializes the data. Used for Serializable.
     *
     * @return ?string A string of serialized data.
     */
    public function serialize(): ?string
    {
        return serialize($this->getRawProperties());
    }

    public function __serialize(): array
    {
        return $this->getRawProperties();
    }

    /**
     * Unserializes some data and stores it. Used for Serializable.
     *
     * @param string $data Some serialized data.
     *
     * @see Part::setProperty() The unserialized data is stored with setProperty.
     */
    public function unserialize($data): void
    {
        $data = unserialize($data);

        foreach ($data as $key => $value) {
            $this->setProperty($key, $value);
        }
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->setProperty($key, $value);
        }
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
     * Handles dynamic get calls onto the part.
     *
     * @param string $key The properties key.
     *
     * @return mixed The value of the property.
     *
     * @throws \Exception
     * @see Part::getProperty() This function forwards onto getProperty.
     */
    public function __get(string $key)
    {
        return $this->getProperty($key);
    }

    /**
     * Handles dynamic set calls onto the part.
     *
     * @param string $key   The properties key.
     * @param mixed  $value The properties value.
     *
     * @see self::setProperty() This function forwards onto setProperty.
     */
    public function __set(string $key, $value): void
    {
        $this->setProperty($key, $value);
    }
}
