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

namespace Discord\OAuth2;

/**
 * A user's OAuth2 access token, as Discord issued it.
 *
 * Immutable: refreshing produces a new token rather than changing this one. The
 * expiry is recorded as a timestamp when the token arrives, so it stays correct
 * however long the token is kept.
 *
 * The secrets are never shown by `var_dump()` or `print_r()`.
 *
 * @since 10.59.0
 *
 * @link https://docs.discord.com/developers/topics/oauth2#authorization-code-grant-access-token-response
 */
final class AccessToken implements \JsonSerializable
{
    /**
     * @param string      $access_token  The token itself.
     * @param string      $token_type    Always `Bearer` for a user's token.
     * @param string|null $refresh_token What to exchange for a new token when this one expires. Provisional account tokens have none.
     * @param int|null    $expires_at    When the token expires, as a Unix timestamp.
     * @param string[]    $scopes        The scopes the token was granted.
     * @param string|null $id_token      An OpenID Connect ID token, when one was issued.
     */
    public function __construct(
        public readonly string $access_token,
        public readonly string $token_type = 'Bearer',
        public readonly ?string $refresh_token = null,
        public readonly ?int $expires_at = null,
        public readonly array $scopes = [],
        public readonly ?string $id_token = null,
    ) {
    }

    /**
     * Builds a token from a token response, such as the one `oauth2/token` or `partner-sdk/token/bot` returns.
     *
     * @param object|array $response The decoded response.
     * @param int|null     $now      The time the response arrived, as a Unix timestamp; now if omitted.
     *
     * @throws \InvalidArgumentException The response carries no access token.
     */
    public static function fromResponse(object|array $response, ?int $now = null): self
    {
        $response = (array) $response;

        if (! isset($response['access_token']) || '' === $response['access_token']) {
            throw new \InvalidArgumentException('The response carries no access token.');
        }

        $expires_in = isset($response['expires_in']) ? (int) $response['expires_in'] : null;

        return new self(
            (string) $response['access_token'],
            (string) ($response['token_type'] ?? 'Bearer'),
            isset($response['refresh_token']) ? (string) $response['refresh_token'] : null,
            null === $expires_in ? null : ($now ?? time()) + $expires_in,
            array_values(array_filter(explode(' ', (string) ($response['scope'] ?? '')))),
            isset($response['id_token']) ? (string) $response['id_token'] : null,
        );
    }

    /**
     * Rebuilds a token from what {@see jsonSerialize()} produced, as a token store keeps it.
     *
     * @param array $stored
     */
    public static function fromArray(array $stored): self
    {
        return new self(
            (string) $stored['access_token'],
            (string) ($stored['token_type'] ?? 'Bearer'),
            $stored['refresh_token'] ?? null,
            isset($stored['expires_at']) ? (int) $stored['expires_at'] : null,
            (array) ($stored['scopes'] ?? []),
            $stored['id_token'] ?? null,
        );
    }

    /**
     * Whether the token has expired, or will within `$leeway` seconds.
     *
     * A token without a known expiry is never considered expired.
     *
     * @param int      $leeway How early to treat it as expired, so a request made now does not race the expiry.
     * @param int|null $now    The current time, as a Unix timestamp; now if omitted.
     */
    public function isExpired(int $leeway = 60, ?int $now = null): bool
    {
        return null !== $this->expires_at && ($now ?? time()) + $leeway >= $this->expires_at;
    }

    /** Whether the token can be refreshed through `oauth2/token`. */
    public function isRefreshable(): bool
    {
        return null !== $this->refresh_token;
    }

    /** The value for an `Authorization` header. */
    public function authorization(): string
    {
        return "{$this->token_type} {$this->access_token}";
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'access_token' => $this->access_token,
            'token_type' => $this->token_type,
            'refresh_token' => $this->refresh_token,
            'expires_at' => $this->expires_at,
            'scopes' => $this->scopes,
            'id_token' => $this->id_token,
        ];
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return [
            'access_token' => '*****',
            'token_type' => $this->token_type,
            'refresh_token' => null === $this->refresh_token ? null : '*****',
            'expires_at' => $this->expires_at,
            'scopes' => $this->scopes,
            'id_token' => null === $this->id_token ? null : '*****',
        ];
    }
}
