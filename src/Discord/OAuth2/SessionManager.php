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

use Discord\Discord;
use Discord\Http\Endpoint;
use Discord\Http\Http;
use Discord\OAuth2\TokenStore\TokenStoreInterface;
use React\Promise\PromiseInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Opens, stores, resumes, refreshes and revokes users' sessions — `$discord->sessions`.
 *
 * A session acts as one user with their own OAuth2 token: see {@see Session}.
 * Tokens given a key are kept in the token store (the `tokenStore` option), so
 * a session survives a restart; the most recently used sessions, up to a limit,
 * are also kept in memory.
 *
 * Provisional accounts — Discord accounts for players who have not linked one —
 * are created here too, with the bot token. Exchanging an external identity
 * provider's token, unmerging by one, exchanging for a child application's
 * token, and refreshing an expired token all authenticate as the application
 * instead, and need the `clientSecret` option.
 *
 * @since 10.59.0
 *
 * @link https://docs.discord.com/developers/discord-social-sdk/development-guides/provisional-accounts/overview
 */
class SessionManager
{
    /** How many keyed sessions to keep in memory. */
    public const DEFAULT_LIMIT = 1000;

    /**
     * Keyed sessions, least recently used first.
     *
     * @var array<string, Session>
     */
    protected array $sessions = [];

    /** Sends no Authorization, for routes that take the application's credentials in the body. */
    protected ?Http $credentialsHttp = null;

    /**
     * @param Discord             $discord      The client.
     * @param TokenStoreInterface $store        Where keyed tokens are kept.
     * @param string|null         $clientSecret The application's client secret, for refreshing and client-credential calls.
     * @param int                 $limit        How many keyed sessions to keep in memory.
     */
    public function __construct(
        protected Discord $discord,
        protected TokenStoreInterface $store,
        protected ?string $clientSecret = null,
        protected int $limit = self::DEFAULT_LIMIT,
    ) {
    }

    /**
     * Returns the public keys that sign Discord's OpenID Connect ID tokens, as the `keys` of a JSON Web Key
     * Set, ready to give a JWT library to verify an ID token with. They need no token to read.
     *
     * Discord describes this endpoint in its OpenAPI description rather than in its documentation.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc7517#section-5
     *
     * @return PromiseInterface<list<array<string, mixed>>>
     *
     * @since 10.60.0
     */
    public function getPublicKeys(): PromiseInterface
    {
        return $this->discord->getHttpClient()->get(Endpoint::OAUTH2_KEYS)
            ->then(fn ($response): array => json_decode(json_encode($response->keys ?? [], JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * Opens a session with a token, storing it under `$key` if one is given.
     *
     * @param AccessToken|object|array $token A token, or a token response from Discord.
     * @param string|null              $key   What to store the token under, such as your own user id; not stored if omitted.
     *
     * @return PromiseInterface<Session>
     */
    public function open($token, ?string $key = null): PromiseInterface
    {
        try {
            $token = $token instanceof AccessToken ? $token : AccessToken::fromResponse($token);
        } catch (\InvalidArgumentException $e) {
            return reject($e);
        }

        $session = new Session($this->discord, $token, $key);

        if (null === $key) {
            return resolve($session);
        }

        $this->remember($key, $session);

        return $this->store->set($key, $token)->then(static fn () => $session);
    }

    /**
     * Exchanges an authorization code for a user's token and opens a session as them.
     *
     * For confidential clients: requires the `clientSecret` option. The caller must validate the
     * authorization callback's state before calling this, and handle a denied authorization.
     *
     * @link https://docs.discord.com/developers/topics/oauth2#authorization-code-grant
     *
     * @param string      $code         The authorization code returned by Discord.
     * @param string      $redirect_uri The exact redirect URI used for the authorization request.
     * @param string|null $key          What to store the token under; not stored if omitted.
     *
     * @return PromiseInterface<Session>
     *
     * @since 10.67.0
     */
    public function exchangeAuthorizationCode(string $code, string $redirect_uri, ?string $key = null): PromiseInterface
    {
        return $this->withCredentials(function (string $client_id, string $client_secret) use ($code, $redirect_uri, $key) {
            $http = new Http('Basic '.base64_encode("{$client_id}:{$client_secret}"), $this->discord->getLoop(), $this->discord->getLogger(), $this->discord->getHttpClient()->getDriver());

            return $http->post(
                Endpoint::OAUTH2_TOKEN,
                http_build_query(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $redirect_uri]),
                ['Content-Type' => 'application/x-www-form-urlencoded']
            )->then(fn ($response) => $this->open(AccessToken::fromResponse($response, origin: AccessToken::ORIGIN_OAUTH2), $key));
        });
    }

    /**
     * Revokes a user's OAuth2 authorization at Discord, then forgets this session's stored token.
     *
     * Discord invalidates all access and refresh tokens for the authorization, not only this token.
     * The caller must also clear their account mapping and any other local keys for that user.
     * Requires the `clientSecret` option. On HTTP failure the local session and token are retained.
     * Retained Session objects must no longer be used after a successful revocation.
     *
     * @link https://docs.discord.com/developers/topics/oauth2#token-revocation-example
     * @link https://docs.discord.com/developers/discord-social-sdk/development-guides/unlinking-accounts
     *
     * @param Session $session The session whose authorization to revoke.
     *
     * @return PromiseInterface<bool> True for an unkeyed session, otherwise the token store's deletion result.
     *
     * @since 10.67.0
     */
    public function revoke(Session $session): PromiseInterface
    {
        return $this->withCredentials(function (string $client_id, string $client_secret) use ($session) {
            $http = new Http('Basic '.base64_encode("{$client_id}:{$client_secret}"), $this->discord->getLoop(), $this->discord->getLogger(), $this->discord->getHttpClient()->getDriver());

            return $http->post(
                Endpoint::OAUTH2_TOKEN_REVOKE,
                http_build_query(['token' => $session->getToken()->access_token, 'token_type_hint' => 'access_token']),
                ['Content-Type' => 'application/x-www-form-urlencoded']
            )->then(fn () => null === $session->getKey() ? true : $this->forget($session->getKey()));
        });
    }

    /**
     * Returns a keyed session that is already open, without touching the token store.
     *
     * @param string $key What the token is stored under.
     */
    public function get(string $key): ?Session
    {
        if (! isset($this->sessions[$key])) {
            return null;
        }

        $session = $this->sessions[$key];
        $this->remember($key, $session);

        return $session;
    }

    /**
     * Returns the session stored under `$key`, refreshing its token first if it has expired and can be refreshed.
     *
     * @param string $key What the token is stored under.
     *
     * @return PromiseInterface<?Session> Null if nothing is stored under the key.
     *
     * @throws \DomainException Through rejection for expired known provisional or child origins.
     */
    public function resume(string $key): PromiseInterface
    {
        $open = $this->get($key);

        $session = null !== $open
            ? resolve($open)
            : $this->store->get($key)->then(fn (?AccessToken $token) => null === $token ? null : $this->remember($key, new Session($this->discord, $token, $key)));

        return $session->then(fn (?Session $session) => null !== $session && $session->getToken()->isExpired() && ($session->getToken()->isRefreshable() || $session->getToken()->requiresReacquisition())
            ? $this->refresh($session)
            : $session);
    }

    /**
     * Exchanges a session's refresh token for a new token, and stores it if the session is keyed.
     *
     * Discord replaces the refresh token as well, and the old one stops working.
     * Known provisional and child origins reject without a request; repeat their issuance instead.
     *
     * @link https://docs.discord.com/developers/topics/oauth2#authorization-code-grant-refresh-token-exchange-example
     *
     * @param Session $session
     *
     * @return PromiseInterface<Session>
     */
    public function refresh(Session $session): PromiseInterface
    {
        $token = $session->getToken();

        if ($token->requiresReacquisition()) {
            $action = match ($token->origin) {
                AccessToken::ORIGIN_PROVISIONAL_BOT => 'Call createProvisionalAccount() again with the same external user ID.',
                AccessToken::ORIGIN_PROVISIONAL_EXTERNAL => 'Obtain a fresh identity-provider token and call exchangeExternalToken() again.',
                AccessToken::ORIGIN_PROVISIONAL_PUBLIC => 'Obtain a fresh provider token and repeat Public Client issuance, then open() the classified token.',
                AccessToken::ORIGIN_CHILD => 'Call exchangeChildToken() again with a valid parent token.',
            };

            return reject(new \DomainException('This token requires reacquisition. '.$action.' Use the same storage key for a keyed session.'));
        }

        $refresh_token = $token->refresh_token;

        if (null === $refresh_token) {
            return reject(new \DomainException('This token has no refresh token. Obtain a new token through its original issuance method.'));
        }

        return $this->withCredentials(function (string $client_id, string $client_secret) use ($session, $refresh_token, $token) {
            $http = new Http('Basic '.base64_encode("{$client_id}:{$client_secret}"), $this->discord->getLoop(), $this->discord->getLogger(), $this->discord->getHttpClient()->getDriver());

            return $http->post(
                Endpoint::OAUTH2_TOKEN,
                http_build_query(['grant_type' => 'refresh_token', 'refresh_token' => $refresh_token]),
                ['Content-Type' => 'application/x-www-form-urlencoded']
            )->then(function ($response) use ($session, $token) {
                $session->useToken(AccessToken::fromResponse($response, origin: $token->origin));

                return null === $session->getKey()
                    ? $session
                    : $this->store->set($session->getKey(), $session->getToken())->then(static fn () => $session);
            });
        });
    }

    /**
     * Closes a keyed session and removes its token from the store.
     *
     * @param string $key What the token is stored under.
     *
     * @return PromiseInterface<bool>
     */
    public function forget(string $key): PromiseInterface
    {
        unset($this->sessions[$key]);

        return $this->store->delete($key);
    }

    /**
     * Creates a provisional account for a player in your own account system, or returns the one already made, and opens a session as it.
     *
     * Uses the bot token. Provisional tokens cannot be refreshed; call this again for a new one when it expires.
     *
     * @link https://docs.discord.com/developers/discord-social-sdk/development-guides/provisional-accounts/bot-token-endpoint
     *
     * @param string      $external_user_id      The player's unique id in your account system.
     * @param string|null $preferred_global_name The player's display name in your system.
     * @param string|null $key                   What to store the token under; not stored if omitted.
     *
     * @return PromiseInterface<Session>
     */
    public function createProvisionalAccount(string $external_user_id, ?string $preferred_global_name = null, ?string $key = null): PromiseInterface
    {
        $payload = ['external_user_id' => $external_user_id];

        if (null !== $preferred_global_name) {
            $payload['preferred_global_name'] = $preferred_global_name;
        }

        return $this->discord->getHttpClient()->post(Endpoint::PARTNER_SDK_TOKEN_BOT, $payload)
            ->then(fn ($response) => $this->open(AccessToken::fromResponse($response, origin: AccessToken::ORIGIN_PROVISIONAL_BOT), $key));
    }

    /**
     * Separates a provisional account from the Discord account it was merged into, using the bot token.
     *
     * @link https://docs.discord.com/developers/discord-social-sdk/development-guides/provisional-accounts/unmerging-accounts
     *
     * @param string $external_user_id The player's unique id in your account system, as given to {@see createProvisionalAccount()}.
     *
     * @return PromiseInterface
     */
    public function unmergeProvisionalAccount(string $external_user_id): PromiseInterface
    {
        return $this->discord->getHttpClient()->post(Endpoint::PARTNER_SDK_PROVISIONAL_ACCOUNTS_UNMERGE_BOT, ['external_user_id' => $external_user_id]);
    }

    /**
     * Exchanges a token from an external identity provider for a Discord token, creating a provisional account if needed, and opens a session as it.
     *
     * Needs the `clientSecret` option, and the provider configured in the Developer Portal.
     *
     * @link https://docs.discord.com/developers/discord-social-sdk/development-guides/provisional-accounts/external-credentials-exchange
     *
     * @param string      $external_auth_type  The provider type, such as `OIDC`, `STEAM_SESSION_TICKET` or `EPIC_ONLINE_SERVICES_ID_TOKEN`.
     * @param string      $external_auth_token The provider's token.
     * @param string|null $key                 What to store the token under; not stored if omitted.
     *
     * @return PromiseInterface<Session>
     */
    public function exchangeExternalToken(string $external_auth_type, string $external_auth_token, ?string $key = null): PromiseInterface
    {
        return $this->withCredentials(fn (string $client_id, string $client_secret) => $this->credentialsHttp()->post(Endpoint::PARTNER_SDK_TOKEN, [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'external_auth_type' => $external_auth_type,
            'external_auth_token' => $external_auth_token,
        ])->then(fn ($response) => $this->open(AccessToken::fromResponse($response, origin: AccessToken::ORIGIN_PROVISIONAL_EXTERNAL), $key)));
    }

    /**
     * Separates a provisional account from the Discord account it was merged into, identifying the player by an external provider's token.
     *
     * Needs the `clientSecret` option.
     *
     * @link https://docs.discord.com/developers/discord-social-sdk/development-guides/provisional-accounts/unmerging-accounts
     *
     * @param string $external_auth_type  The provider type.
     * @param string $external_auth_token The provider's token.
     *
     * @return PromiseInterface
     */
    public function unmergeExternalAccount(string $external_auth_type, string $external_auth_token): PromiseInterface
    {
        return $this->withCredentials(fn (string $client_id, string $client_secret) => $this->credentialsHttp()->post(Endpoint::PARTNER_SDK_PROVISIONAL_ACCOUNTS_UNMERGE, [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'external_auth_type' => $external_auth_type,
            'external_auth_token' => $external_auth_token,
        ]));
    }

    /**
     * Exchanges a publisher application's token for one of its child applications' tokens, and opens a session as it.
     *
     * The bot must be the publisher (parent) application; needs the `clientSecret` option. Child tokens cannot be refreshed.
     *
     * @link https://docs.discord.com/developers/discord-social-sdk/development-guides/publisher-level-account-linking
     *
     * @param Session|AccessToken $parent               The publisher application's session or token for the player.
     * @param string              $child_application_id The game's application id.
     * @param string|null         $key                  What to store the child token under; not stored if omitted.
     *
     * @return PromiseInterface<Session>
     */
    public function exchangeChildToken($parent, string $child_application_id, ?string $key = null): PromiseInterface
    {
        $parent_token = $parent instanceof Session ? $parent->getToken() : $parent;

        return $this->withCredentials(fn (string $client_id, string $client_secret) => $this->credentialsHttp()->post(Endpoint::PARTNER_SDK_CHILD_TOKEN, [
            'parent_access_token' => $parent_token->access_token,
            'child_application_id' => $child_application_id,
            'parent_client_secret' => $client_secret,
        ])->then(fn ($response) => $this->open(AccessToken::fromResponse($response, origin: AccessToken::ORIGIN_CHILD), $key)));
    }

    /**
     * Keeps a keyed session in memory as the most recently used, dropping the least recently used past the limit.
     */
    protected function remember(string $key, Session $session): Session
    {
        unset($this->sessions[$key]);
        $this->sessions[$key] = $session;

        while (count($this->sessions) > $this->limit) {
            unset($this->sessions[array_key_first($this->sessions)]);
        }

        return $session;
    }

    /**
     * Runs a call that authenticates as the application, once its id and secret are known.
     *
     * @param callable(string, string): PromiseInterface $call Given the client id and secret.
     *
     * @return PromiseInterface
     */
    protected function withCredentials(callable $call): PromiseInterface
    {
        if (null === $this->clientSecret) {
            return reject(new \DomainException('This needs the application\'s client secret: pass it as the `clientSecret` option.'));
        }

        $client_id = $this->discord->application?->id;

        if (null === $client_id) {
            return reject(new \DomainException('The application is not known yet: wait for the client to be ready.'));
        }

        return $call((string) $client_id, $this->clientSecret);
    }

    /**
     * A client that sends no Authorization, sharing the bot's driver.
     */
    protected function credentialsHttp(): Http
    {
        return $this->credentialsHttp ??= new Http('', $this->discord->getLoop(), $this->discord->getLogger(), $this->discord->getHttpClient()->getDriver());
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return ['sessions' => array_keys($this->sessions), 'limit' => $this->limit, 'clientSecret' => null === $this->clientSecret ? null : '*****'];
    }
}
