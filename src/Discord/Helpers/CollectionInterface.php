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

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

interface CollectionInterface extends ArrayAccess, JsonSerializable, IteratorAggregate, Countable
{
    public function get(string $discrim, $key);
    public function set($offset, $value);
    public function pull($key, $default = null);
    public function fill(array $items): static;
    public function push(...$items): static;
    public function pushItem($item): static;
    public function count(): int;
    public function first();
    public function last();
    public function isset($offset): bool;
    public function has(...$keys): bool;
    public function filter(callable $callback): static;
    public function find(callable $callback);
    public function clear(): void;
    public function map(callable $callback): static;
    public function merge($collection): static;
    public function toArray();
    public function offsetExists($offset): bool;
    #[\ReturnTypeWillChange]
    public function offsetGet($offset);
    public function offsetSet($offset, $value): void;
    public function offsetUnset($offset): void;
    public function serialize(): string;
    public function __serialize(): array;
    public function unserialize(string $serialized): void;
    public function __unserialize(array $data): void;
    public function jsonSerialize(): array;
    public function getIterator(): Traversable;
    public function __debugInfo(): array;
}
