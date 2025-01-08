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

use Discord\Discord;
use React\Promise\PromiseInterface;

/**
 * This class is the base of all objects that are returned. All "Parts" extend
 * off this base class.
 *
 * @since 2.0.0
 */
interface PartInterface
{
    public function __construct(Discord $discord, array $attributes = [], bool $created = false);
    //protected function afterConstruct(): void;
    public function isPartial(): bool;
    public function fetch(): PromiseInterface;
    public function fill(array $attributes): void;
    //private function checkForGetMutator(string $key);
    //private function checkForSetMutator(string $key);
    //private function getAttribute(string $key);
    //private function setAttribute(string $key, $value): void;
    #[\ReturnTypeWillChange]
    public function offsetGet($key);
    public function offsetExists($key): bool;
    public function offsetSet($key, $value): void;
    public function offsetUnset($key): void;
    public function serialize(): ?string;
    public function __serialize(): array;
    public function unserialize($data): void;
    public function __unserialize(array $data): void;
    public function jsonSerialize(): array;
    public function getPublicAttributes(): array;
    public function getRawAttributes(): array;
    public function getRepositoryAttributes(): array;
    public function getCreatableAttributes(): array;
    public function getUpdatableAttributes(): array;
    //protected function makeOptionalAttributes(array $attributes): array;
    public function getDiscord(): Discord;
    public function createOf(string $class, array|object $data): self;
    //private static function studly(string $string): string;
    public function __toString(): string;
    public function __debugInfo(): array;
    public function __get(string $key);
    public function __set(string $key, $value): void;
}
