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

namespace Discord\Builders\ApplicationIdentityProfile;

/**
 * Complete outbound stat snapshots and their HTTP wire representation.
 *
 * @internal
 */
final class ProfileDataValidator
{
    /** Normalize Parts and JSON objects, then validate before storing builder state. */
    public static function normalize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }
        // Match HTTP encoding, including escaped Unicode and slashes.
        $normalized = self::decode($data);
        self::validate($normalized);

        return $normalized;
    }

    /** Apply section rules and the serialized data limit, excluding username. */
    private static function validate(array $data): void
    {
        if (array_diff(array_keys($data), ['primary', 'dynamic'])) {
            throw new \InvalidArgumentException('Unknown profile data field.');
        }
        $validators = [
            'primary' => PrimaryProfileDataValidator::validate(...),
            'dynamic' => DynamicProfileDataValidator::validate(...),
        ];
        foreach ($data as $key => $value) {
            $validators[$key]($value);
        }
        if (strlen(json_encode(self::object($data), JSON_THROW_ON_ERROR)) > 10_240) {
            throw new \LengthException('Serialized profile data cannot exceed 10 KB.');
        }
    }

    /** Serialize only supplied fields, preserving explicit nulls. */
    public static function payload(array $payload): array
    {
        if (($payload['data'] ?? null) !== null) {
            $payload['data'] = self::object($payload['data']);
        }

        return $payload;
    }

    /** Empty data and primary are JSON objects; dynamic is a JSON array. */
    private static function object(array $data): object
    {
        if (array_key_exists('primary', $data)) {
            $data['primary'] = (object) $data['primary'];
        }

        return (object) $data;
    }

    /** Convert nested Parts and objects to arrays with the HTTP client's encoding. */
    private static function decode(array $data): array
    {
        $json = json_encode((object) $data, JSON_THROW_ON_ERROR);

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
