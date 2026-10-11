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

namespace Discord\Builders;

use Discord\Parts\Application\Identity\ApplicationIdentityProfile;
use Discord\Parts\Application\Identity\DynamicField;
use Discord\Parts\Application\Identity\PrimaryProfileData;
use Discord\Parts\Application\Identity\ProfileData;
use Discord\Parts\Part;
use Discord\Repository\ApplicationIdentityRepository;
use JsonSerializable;
use React\Promise\PromiseInterface;

use function Discord\poly_strlen;

/**
 * Authors a complete replacement of game stats, or a username-only update.
 *
 * @since 10.59.0
 * @link https://docs.discord.com/developers/resources/application-identity-profile#update-application-identity-profile
 *
 * @property string|null $username External username.
 * @property array|null  $data     Complete profile data; omitted until explicitly set.
 */
class ApplicationIdentityProfileBuilder extends Builder implements JsonSerializable
{
    /** @var array<string, mixed> Only explicitly supplied request fields. */
    protected array $payload = [];

    /** @return static */
    public static function new(): static
    {
        return new static();
    }

    /**
     * Copies writable fields only; response metadata is not a PATCH parameter.
     *
     * Nullable response fields and absent fields remain distinct.
     *
     * @param  Part $part An ApplicationIdentityProfile.
     * @return self
     */
    public static function fromPart(Part $part): self
    {
        if (! $part instanceof ApplicationIdentityProfile) {
            throw new \InvalidArgumentException('Expected an ApplicationIdentityProfile.');
        }

        $builder = new static();
        $attributes = $part->getRawAttributes();
        if (array_key_exists('username', $attributes)) {
            $builder->setUsername($attributes['username']);
        }
        if (array_key_exists('data', $attributes)) {
            $builder->setData($attributes['data']);
        }

        return $builder;
    }

    /** Set the external username (maximum 1024 characters). */
    public function setUsername(?string $username): self
    {
        if ($username !== null) {
            self::text($username, 1024, 'username');
        }
        $this->payload['username'] = $username;

        return $this;
    }

    /** @return string|null */
    public function getUsername(): ?string
    {
        return $this->payload['username'] ?? null;
    }

    /**
     * Replace all stored stats. Null is preserved for existing array authoring parity.
     * Discord documents object writes; null response values are not a promised clearing operation.
     *
     * @param  array|ProfileData|null $data The complete primary/dynamic data.
     * @return self
     */
    public function setData(array|ProfileData|null $data): self
    {
        $data = $data instanceof ProfileData ? $data->getRawAttributes() : $data;
        if ($data !== null) {
            // Match the HTTP client's JSON encoding, including escaped Unicode and slashes.
            $data = json_decode(json_encode((object) $data, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
            self::validateData($data);
        }
        $this->payload['data'] = $data;

        return $this;
    }

    /** @return array|null */
    public function getData(): ?array
    {
        return $this->payload['data'] ?? null;
    }

    /** Omit data so publishing changes only the username. */
    public function omitData(): self
    {
        unset($this->payload['data']);

        return $this;
    }

    /**
     * Publish through the existing identity repository; no additional profile repository.
     *
     * @param  ApplicationIdentityRepository   $repository              Application identities.
     * @param  \Discord\Parts\User\User|string $user                    Discord user.
     * @param  string                          $provider_issued_user_id External account ID.
     * @return PromiseInterface
     */
    public function publish(ApplicationIdentityRepository $repository, $user, string $provider_issued_user_id): PromiseInterface
    {
        return $repository->updateProfile($user, $provider_issued_user_id, $this);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $payload = $this->payload;
        if (isset($payload['data'])) {
            // PHP's empty array encodes as [], but data and primary are JSON objects.
            if (isset($payload['data']['primary'])) {
                $payload['data']['primary'] = (object) $payload['data']['primary'];
            }
            $payload['data'] = (object) $payload['data'];
        }

        return $payload;
    }

    /** Validate documented limits without imposing undocumented numeric ranges. */
    private static function validateData(array $data): void
    {
        foreach ($data as $key => $value) {
            if (! in_array($key, ['primary', 'dynamic'], true)) {
                throw new \InvalidArgumentException('Unknown profile data field: '.$key);
            }
        }
        if (array_key_exists('primary', $data)) {
            if (! is_array($data['primary'])) {
                throw new \InvalidArgumentException('primary must be an object.');
            }
            foreach ($data['primary'] as $key => $value) {
                if (in_array($key, PrimaryProfileData::STRING_FIELDS, true)) {
                    self::text($value, 100, $key);
                } elseif (in_array($key, PrimaryProfileData::MEDIA_FIELDS, true)) {
                    self::media($value);
                } elseif ($key === 'playtime_hours') {
                    self::number($value);
                } elseif (in_array($key, PrimaryProfileData::INTEGER_FIELDS, true)) {
                    if (! is_int($value)) {
                        throw new \InvalidArgumentException($key.' must be an integer.');
                    }
                } else {
                    throw new \InvalidArgumentException('Unknown primary field: '.$key);
                }
            }
        }
        if (array_key_exists('dynamic', $data)) {
            if (! is_array($data['dynamic']) || ! array_is_list($data['dynamic'])) {
                throw new \InvalidArgumentException('dynamic must be a list.');
            }
            if (count($data['dynamic']) > 30) {
                throw new \LengthException('At most 30 dynamic fields are allowed.');
            }
            foreach ($data['dynamic'] as $field) {
                if (! is_array($field) || ! is_int($field['type'] ?? null) || ! isset(DynamicField::TYPES[$field['type'] ?? 0]) || ($field['type'] ?? 0) === 0) {
                    throw new \InvalidArgumentException('Unknown dynamic field type.');
                }
                if (! is_int($field['type']) || ! array_key_exists('value', $field) || array_diff(array_keys($field), ['type', 'name', 'value'])) {
                    throw new \InvalidArgumentException('Invalid dynamic field shape.');
                }
                self::text($field['name'] ?? null, 100, 'name');
                $validators = [
                    DynamicField::TYPE_STRING => static fn ($value) => self::text($value, 100, 'value'),
                    DynamicField::TYPE_NUMBER => static fn ($value) => self::number($value),
                    DynamicField::TYPE_MEDIA => static fn ($value) => self::media($value),
                ];
                $validators[$field['type']]($field['value']);
            }
        }
        $wire = $data;
        if (isset($wire['primary'])) {
            $wire['primary'] = (object) $wire['primary'];
        }
        if (strlen(json_encode((object) $wire, JSON_THROW_ON_ERROR)) > 10240) {
            throw new \LengthException('Serialized profile data cannot exceed 10 KB.');
        }
    }

    private static function text($value, int $limit, string $field): void
    {
        if (! is_string($value)) {
            throw new \InvalidArgumentException($field.' must be a string.');
        }
        if (poly_strlen($value) > $limit) {
            throw new \LengthException($field.' cannot exceed '.$limit.' characters.');
        }
    }

    private static function number($value): void
    {
        if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
            throw new \InvalidArgumentException('Stat values must be finite numbers.');
        }
    }

    private static function media($value): void
    {
        if (! is_array($value) || array_keys($value) !== ['url'] || ! is_string($value['url'])
            || ! filter_var($value['url'], FILTER_VALIDATE_URL)
            || ! in_array(strtolower(parse_url($value['url'], PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Media requires an HTTP(S) url.');
        }
    }
}
