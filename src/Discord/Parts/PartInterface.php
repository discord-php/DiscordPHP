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

namespace Discord\Parts;

use Discord\Discord;
use React\Promise\PromiseInterface;

/**
 * Contract for every DiscordPHP "Part" — the hydrated objects returned for
 * Discord API entities (users, channels, messages, …). Defines construction,
 * attribute access via mutators, `ArrayAccess`, (de)serialization and the
 * attribute projections (`getCreatableAttributes()` / `getUpdatableAttributes()`)
 * the repositories use when POST/PATCHing. {@see Part} is the concrete base.
 *
 * @see Part The abstract implementation every Part extends
 * @see \Discord\Repository\AbstractRepositoryInterface Repositories that hold Parts
 *
 * @since 2.0.0
 */
interface PartInterface
{
    /**
     * @param Discord $discord
     * @param array   $attributes Initial attribute values.
     * @param bool    $created    Whether the Part already exists on Discord.
     */
    public function __construct(Discord $discord, array $attributes = [], bool $created = false);

    //protected function afterConstruct(): void;

    /** Whether the Part holds only a partial set of attributes. */
    public function isPartial(): bool;

    /** Fetches the full Part from the Discord API and refills it in place. */
    public function fetch(): PromiseInterface;

    /** Mass-assigns `$attributes` (respecting the `$fillable` whitelist). */
    public function fill(array $attributes): void;

    //private function checkForGetMutator(string $key);
    //private function checkForSetMutator(string $key);
    //private function getAttribute(string $key);
    //private function setAttribute(string $key, $value): void;

    /** Gets the attribute at `$key` (ArrayAccess). */
    #[\ReturnTypeWillChange]
    public function offsetGet($key);

    /** Whether the attribute at `$key` is set (ArrayAccess). */
    public function offsetExists($key): bool;

    /** Sets the attribute at `$key` (ArrayAccess). */
    public function offsetSet($key, $value): void;

    /** Unsets the attribute at `$key` (ArrayAccess). */
    public function offsetUnset($key): void;

    /** Serialises the Part's attributes to a string, or null on failure. */
    public function serialize(): ?string;

    /** The Part's attributes, for PHP's native `serialize()`. */
    public function __serialize(): array;

    /** Restores the Part from a string produced by {@see serialize()}. */
    public function unserialize($data): void;

    /** Restores the Part from the payload of PHP's native `unserialize()`. */
    public function __unserialize(array $data): void;

    /** The Part's public attributes, for `json_encode()`. */
    public function jsonSerialize(): array;

    /** The attributes safe to expose publicly (secrets stripped). */
    public function getPublicAttributes(): array;

    /** The raw, unmutated attribute array. */
    public function getRawAttributes(): array;

    /** The attributes the owning repository binds into its endpoint URIs. */
    public function getRepositoryAttributes(): array;

    /** The attribute subset sent when creating this Part (POST). */
    public function getCreatableAttributes(): array;

    /** The attribute subset sent when updating this Part (PATCH). */
    public function getUpdatableAttributes(): array;

    //protected function makeOptionalAttributes(array $attributes): array;

    /** The Discord client this Part belongs to. */
    public function getDiscord(): Discord;

    /** Hydrates a new Part of `$class` from `$data` using this Part's client. */
    public function createOf(string $class, array|object $data): self;

    //private static function studly(string $string): string;

    /** A short string representation of the Part. */
    public function __toString(): string;

    /** Debug representation for `var_dump()`. */
    public function __debugInfo(): array;

    /** Magic getter for an attribute or accessor mutator. */
    public function __get(string $key);

    /** Magic setter for an attribute or mutator. */
    public function __set(string $key, $value): void;
}
