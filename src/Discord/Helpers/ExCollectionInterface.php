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

interface ExCollectionInterface extends CollectionInterface
{
    public static function from(array $items = [], ?string $discrim = 'id', ?string $class = null);
    public static function for(string $class, ?string $discrim = 'id');
    public function shift();
    public function search(mixed $needle, bool $strict = false): string|int|false;
    public function find_key(callable $callback);
    public function any(callable $callback): bool;
    public function all(callable $callback): bool;
    public function splice(int $offset, ?int $length, mixed $replacement = []): self;
    public function clear(): void;
    public function slice(int $offset, ?int $length = null, bool $preserve_keys = false);
    public function sort(callable|int|null $callback);
    public function diff($items, ?callable $callback = null);
    public function intersect($items, ?callable $callback = null);
    public function walk(callable $callback, mixed $arg);
    public function reduce(callable $callback, $initial = null);
    public function unique(int $flags = SORT_STRING);
    public function keys(): array;
    public function values(): array;
}
