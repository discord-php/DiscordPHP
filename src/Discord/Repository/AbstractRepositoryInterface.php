<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Helpers\CollectionInterface;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;
use React\Promise\PromiseInterface;
use Traversable;

/**
 * Contract for a DiscordPHP repository: a keyed, cached {@see CollectionInterface}
 * of {@see Part}s for one Discord API resource, plus the async CRUD methods
 * (`freshen()`, `fetch()`, `create()`, `save()`, `delete()`) that talk to the
 * Discord REST API and keep the cache in sync. {@see AbstractRepository} is the
 * concrete base every repository extends.
 *
 * @see AbstractRepository The abstract implementation
 * @see \Discord\Parts\PartInterface The items held by a repository
 *
 * @since 10.1.4
 */
interface AbstractRepositoryInterface extends CollectionInterface
{
    /**
     * @param \Discord\Discord $discord
     * @param array            $vars    Variables bound into the repository's endpoint URIs.
     */
    public function __construct($discord, array $vars = []);

    /**
     * Returns the repository's items as a new collection.
     *
     * @return ExCollectionInterface
     */
    public function collect();

    /**
     * Freshens the repository from the Discord API and resolves with itself.
     *
     * @param array $queryparams Query string parameters for the list endpoint.
     */
    public function freshen(array $queryparams = []): PromiseInterface;

    /**
     * Builds a new, un-saved Part of this repository's class from `$attributes`.
     *
     * @param array|object $attributes
     * @param bool         $created    Whether the Part should be marked as already existing on Discord.
     */
    public function create(array|object $attributes = [], bool $created = false): Part;

    /**
     * @deprecated 10.38.0 Use `Part->save($reason)` to ensure permissions are checked.
     */
    public function save(Part $part, ?string $reason = null): PromiseInterface;

    /**
     * Deletes `$part` (a Part or its id) on Discord and removes it from the repository.
     *
     * @param Part|string $part
     * @param string|null $reason Audit-log reason.
     */
    public function delete($part, ?string $reason = null): PromiseInterface;

    /**
     * Re-fetches `$part` from the Discord API and refills it in place.
     *
     * @param array $queryparams Query string parameters for the fetch.
     */
    public function fresh(Part $part, array $queryparams = []): PromiseInterface;

    /**
     * Fetches the Part with id `$id`, resolving from cache unless `$fresh` is true.
     */
    public function fetch(string $id, bool $fresh = false): PromiseInterface;

    /**
     * @inheritDoc
     */
    public function get(string $discrim, $key);

    /**
     * Resolves with the cached item at `$offset`, loading it from the cache backend if needed.
     */
    public function cacheGet($offset): PromiseInterface;

    /**
     * @inheritDoc
     */
    public function set($offset, $value);

    /**
     * @inheritDoc
     */
    public function pull($key, $default = null);

    /**
     * Removes and resolves with the cached item at `$key` (or `$default`).
     */
    public function cachePull($key, $default = null): PromiseInterface;

    /**
     * @inheritDoc
     */
    public function pushItem($item): self;

    /**
     * @inheritDoc
     */
    public function first();

    /**
     * @inheritDoc
     */
    public function last();

    /**
     * @inheritDoc
     */
    public function has(...$keys): bool;

    /**
     * @inheritDoc
     *
     * @return ExCollectionInterface
     */
    public function filter(callable $callback);

    /**
     * @inheritDoc
     */
    public function find(callable $callback);

    /**
     * @inheritDoc
     */
    public function clear(): void;

    /**
     * @deprecated 10.42.0 Use `jsonSerialize`
     */
    public function toArray(bool $assoc = true): array;

    /**
     * @inheritDoc
     */
    public function keys(): array;

    /**
     * @inheritDoc
     */
    public function values(): array;

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool;

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset);

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void;

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void;

    /**
     * @inheritDoc
     */
    public function jsonSerialize(bool $assoc = true): array;

    /**
     * @inheritDoc
     */
    public function &getIterator(): Traversable;

    /**
     * Reads an otherwise-protected repository property (`discrim`, `cache`, …).
     *
     * @param string $key
     *
     * @return mixed
     */
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
