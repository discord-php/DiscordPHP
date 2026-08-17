<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use Psr\SimpleCache\CacheInterface;

/**
 * A no-op cache that never stores values and always returns the provided default.
 *
 * This class is conditionally defined to remain compatible with
 * psr/simple-cache v1, v2 and v3 method signatures.
 *
 * @since v10.48.0
 */
if ((new \ReflectionMethod(CacheInterface::class, 'get'))->hasReturnType()) {
    class VoidCache implements CacheInterface
    {
        public function get(string $key, mixed $default = null): mixed
        {
            return $default;
        }

        public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
        {
            return true;
        }

        public function delete(string $key): bool
        {
            return true;
        }

        public function getMultiple(iterable $keys, mixed $default = null): iterable
        {
            $result = [];

            foreach ($keys as $key) {
                $result[$key] = $default;
            }

            return $result;
        }

        public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
        {
            return true;
        }

        public function deleteMultiple(iterable $keys): bool
        {
            return true;
        }

        public function clear(): bool
        {
            return true;
        }

        public function has(string $key): bool
        {
            return false;
        }
    }
} else {
    class VoidCache implements CacheInterface
    {
        public function get($key, $default = null)
        {
            return $default;
        }

        public function set($key, $value, $ttl = null)
        {
            return true;
        }

        public function delete($key)
        {
            return true;
        }

        public function getMultiple($keys, $default = null)
        {
            $result = [];

            foreach ($keys as $key) {
                $result[$key] = $default;
            }

            return $result;
        }

        public function setMultiple($values, $ttl = null)
        {
            return true;
        }

        public function deleteMultiple($keys)
        {
            return true;
        }

        public function clear()
        {
            return true;
        }

        public function has($key)
        {
            return false;
        }
    }
}
