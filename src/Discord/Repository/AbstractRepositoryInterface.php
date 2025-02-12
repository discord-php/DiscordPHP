<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Discord;
use Discord\Helpers\CollectionInterface;
use Discord\Parts\Part;
use React\Promise\PromiseInterface;
use Traversable;

interface AbstractRepositoryInterface extends CollectionInterface
{
    public function __construct(Discord $discord, array $vars = []);
    public function freshen(array $queryparams = []): PromiseInterface;
    public function create(array|object $attributes = [], bool $created = false): Part;
    public function save(Part $part, ?string $reason = null): PromiseInterface;
    public function delete($part, ?string $reason = null): PromiseInterface;
    public function fresh(Part $part, array $queryparams = []): PromiseInterface;
    public function fetch(string $id, bool $fresh = false): PromiseInterface;
    public function get(string $discrim, $key);
    public function cacheGet($offset): PromiseInterface;
    public function set($offset, $value);
    public function pull($key, $default = null);
    public function cachePull($key, $default = null): PromiseInterface;
    public function pushItem($item): self;
    public function first();
    public function last();
    public function has(...$keys): bool;
    public function filter(callable $callback);
    public function find(callable $callback);
    public function clear(): void;
    public function toArray(): array;
    public function keys(): array;
    public function values(): array;
    public function offsetExists($offset): bool;
    #[\ReturnTypeWillChange]
    public function offsetGet($offset);
    public function offsetSet($offset, $value): void;
    public function offsetUnset($offset): void;
    public function jsonSerialize(): array;
    public function &getIterator(): Traversable;
    public function __get(string $key);

    /* Methods imported from CollectionTrait
    public function fill(array $items);
    public function push(...$items);
    public function isset($offset): bool;
    public function slice(int $offset, ?int $length, bool $preserve_keys = false);
    public function sort(callable|int|null $callback);
    public function map(callable $callback);
    public function merge($collection);
    public function serialize(): string;
    public function __serialize(): array;
    public function unserialize(string $serialized): void;
    public function __unserialize(array $data): void;
    public function __debugInfo(): array;
    */
}
