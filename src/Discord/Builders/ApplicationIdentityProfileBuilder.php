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
use Discord\Builders\ApplicationIdentityProfile\ProfileDataValidator;
use Discord\Builders\ApplicationIdentityProfile\ProfileValueValidator;
use Discord\Parts\Application\Identity\ProfileData;
use Discord\Parts\Part;
use Discord\Repository\ApplicationIdentityRepository;
use JsonSerializable;
use React\Promise\PromiseInterface;

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
        foreach (array_intersect_key($part->getRawAttributes(), ['username' => true, 'data' => true]) as $key => $value) {
            $builder->setProperty($key, $value instanceof \stdClass ? (array) $value : $value);
        }

        return $builder;
    }

    /** Set the external username (maximum 1024 characters). */
    public function setUsername(?string $username): self
    {
        if ($username !== null) {
            ProfileValueValidator::text($username, 1024, 'username');
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
        if ($data instanceof ProfileData) {
            $data = $data->getRawAttributes();
        }
        $this->payload['data'] = ProfileDataValidator::normalize($data);

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
        return ProfileDataValidator::payload($this->payload);
    }
}
