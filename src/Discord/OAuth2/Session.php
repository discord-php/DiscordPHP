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
use Discord\Helpers\ExCollectionInterface;
use Discord\Http\Endpoint;
use Discord\Http\Http;
use Discord\Parts\Guild\CommandPermissions;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Command\Permission;
use Discord\Parts\Monetization\Entitlement;
use Discord\Parts\OAuth\Application;
use Discord\Parts\OAuth\Authorization;
use Discord\Parts\OAuth\UserInfo;
use Discord\Parts\Part;
use Discord\Parts\User\ApplicationRoleConnection;
use Discord\Parts\User\Connection;
use Discord\Parts\User\User;
use Discord\Repository\SessionLobbyRepository;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * Acts as one user, with their own OAuth2 token rather than the bot's.
 *
 * This is how a game's backend does what the Social SDK does for a player:
 * joining a lobby, chatting in it, linking a channel. Sessions come from
 * {@see SessionManager} — `$discord->sessions` — which opens, stores, resumes
 * and refreshes them.
 *
 * Each session has its own HTTP client, because Discord rate-limits each token
 * separately, but shares the bot's driver and connection pool.
 *
 * @since 10.59.0
 *
 * @property-read SessionLobbyRepository $lobbies The lobbies the user is in, and what they do in them.
 */
class Session
{
    protected Http $http;

    protected SessionLobbyRepository $lobbies;

    /**
     * @param Discord     $discord The client.
     * @param AccessToken $token   The user's token.
     * @param string|null $key     What the token is stored under, if it is stored.
     */
    public function __construct(
        protected Discord $discord,
        protected AccessToken $token,
        protected ?string $key = null,
    ) {
        $this->http = $this->clientFor($token);
        $this->lobbies = $discord->getFactory()->repository(SessionLobbyRepository::class)->forSession($this);
    }

    /** The user's token. */
    public function getToken(): AccessToken
    {
        return $this->token;
    }

    /** What the token is stored under, or null if it is not stored. */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /** The HTTP client that sends the user's token. */
    public function getHttpClient(): Http
    {
        return $this->http;
    }

    /**
     * Replaces the token, after a refresh.
     *
     * @internal Called by {@see SessionManager::refresh()}.
     */
    public function useToken(AccessToken $token): void
    {
        $this->token = $token;
        $this->http = $this->clientFor($token);
    }

    /**
     * Returns the user the token belongs to.
     *
     * @link https://docs.discord.com/developers/resources/user#get-current-user
     *
     * @return PromiseInterface<User>
     */
    public function getCurrentUser(): PromiseInterface
    {
        return $this->http->get(Endpoint::USER_CURRENT)
            ->then(fn ($response) => $this->discord->getFactory()->part(User::class, (array) $response, true));
    }

    /**
     * Returns the user's connected accounts. Requires the `connections` scope.
     *
     * @link https://docs.discord.com/developers/resources/user#get-current-user-connections
     *
     * @return PromiseInterface<ExCollectionInterface<Connection>|Connection[]>
     */
    public function getConnections(): PromiseInterface
    {
        return $this->http->get(Endpoint::USER_CURRENT_CONNECTIONS)
            ->then(fn ($response) => $this->collect(Connection::class, $response));
    }

    /**
     * Returns the user's entitlements for an application.
     *
     * @link https://docs.discord.com/developers/resources/entitlement
     *
     * @param Application|string|null $application The application or its id; the bot's own if omitted.
     *
     * @return PromiseInterface<ExCollectionInterface<Entitlement>|Entitlement[]>
     */
    public function getEntitlements($application = null): PromiseInterface
    {
        return $this->forApplication($application, fn (string $id) => $this->http->get(Endpoint::bind(Endpoint::USER_CURRENT_APPLICATION_ENTITLEMENTS, $id))
            ->then(fn ($response) => $this->collect(Entitlement::class, $response)));
    }

    /**
     * Returns the user's role connection for an application. Requires the `role_connections.write` scope.
     *
     * @link https://docs.discord.com/developers/resources/user#get-current-user-application-role-connection
     *
     * @param Application|string|null $application The application or its id; the bot's own if omitted.
     *
     * @return PromiseInterface<ApplicationRoleConnection>
     */
    public function getApplicationRoleConnection($application = null): PromiseInterface
    {
        return $this->forApplication($application, fn (string $id) => $this->http->get(Endpoint::bind(Endpoint::USER_CURRENT_APPLICATION_ROLE_CONNECTION, $id))
            ->then(fn ($response) => $this->discord->getFactory()->part(ApplicationRoleConnection::class, (array) $response, true)));
    }

    /**
     * Updates the user's role connection for an application. Requires the `role_connections.write` scope.
     *
     * @link https://docs.discord.com/developers/resources/user#update-current-user-application-role-connection
     *
     * @param ApplicationRoleConnection|array $connection
     * @param ?string                         $connection['platform_name']     The vanity name of the platform (max 50 characters).
     * @param ?string                         $connection['platform_username'] The username on the platform (max 100 characters).
     * @param ?array                          $connection['metadata']          Metadata keys mapped to their string values (max 100 characters each).
     * @param Application|string|null         $application                     The application or its id; the bot's own if omitted.
     *
     * @return PromiseInterface<ApplicationRoleConnection>
     */
    public function updateApplicationRoleConnection($connection, $application = null): PromiseInterface
    {
        if ($connection instanceof ApplicationRoleConnection) {
            $connection = $connection->getRawAttributes();
        }

        return $this->forApplication($application, fn (string $id) => $this->http->put(Endpoint::bind(Endpoint::USER_CURRENT_APPLICATION_ROLE_CONNECTION, $id), $connection)
            ->then(fn ($response) => $this->discord->getFactory()->part(ApplicationRoleConnection::class, (array) $response, true)));
    }

    /**
     * Returns what the user has authorised: the application, the scopes, when the token expires, and the
     * user, when the token has the `identify` scope.
     *
     * @link https://docs.discord.com/developers/topics/oauth2#get-current-authorization-information
     *
     * @return PromiseInterface<Authorization>
     *
     * @since 10.60.0
     */
    public function getAuthorization(): PromiseInterface
    {
        return $this->http->get(Endpoint::OAUTH2_ME)
            ->then(fn ($response) => $this->discord->getFactory()->part(Authorization::class, (array) $response, true));
    }

    /**
     * Returns the user as OpenID Connect describes them. Requires the `openid` scope; the email claims also
     * need the `email` scope.
     *
     * @link https://openid.net/specs/openid-connect-core-1_0.html#UserInfo
     *
     * @return PromiseInterface<UserInfo>
     *
     * @since 10.60.0
     */
    public function getUserInfo(): PromiseInterface
    {
        return $this->http->get(Endpoint::OAUTH2_USERINFO)
            ->then(fn ($response) => $this->discord->getFactory()->part(UserInfo::class, (array) $response, true));
    }

    /**
     * Sets who can use one of an application's commands in a guild, replacing the permissions it had there.
     *
     * Discord only accepts this with a user's token that has the `applications.commands.permissions.update`
     * scope, never a bot's. The user needs the manage_guild and manage_roles permissions in the guild, and
     * must be able to use the command themselves.
     *
     * @link https://docs.discord.com/developers/interactions/application-commands#edit-application-command-permissions
     *
     * @param Guild|string            $guild       The guild.
     * @param Command|string          $command     The command or its ID; the application's ID sets the default for all its commands.
     * @param array<Permission|array> $permissions At most 100, each with `id`, `type` and `permission`.
     * @param Application|string|null $application The application or its id; the bot's own if omitted.
     *
     * @return PromiseInterface<CommandPermissions>
     *
     * @since 10.60.0
     */
    public function setCommandPermissions($guild, $command, array $permissions, $application = null): PromiseInterface
    {
        $guild_id = $guild instanceof Part ? $guild->id : (string) $guild;
        $command_id = $command instanceof Part ? $command->id : (string) $command;
        $permissions = array_map(
            static fn ($permission): array => $permission instanceof Part ? $permission->getRawAttributes() : (array) $permission,
            $permissions,
        );

        return $this->forApplication($application, fn (string $id) => $this->http->put(Endpoint::bind(Endpoint::GUILD_APPLICATION_COMMAND_PERMISSIONS, $id, $guild_id, $command_id), ['permissions' => $permissions])
            ->then(fn ($response) => $this->discord->getFactory()->part(CommandPermissions::class, (array) $response, true)));
    }

    /**
     * Exposes the read-only `lobbies` repository.
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if ('lobbies' === $name) {
            return $this->lobbies;
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return ['token' => $this->token, 'key' => $this->key];
    }

    /**
     * A client that sends this token, sharing the bot's driver.
     */
    protected function clientFor(AccessToken $token): Http
    {
        return new Http($token->authorization(), $this->discord->getLoop(), $this->discord->getLogger(), $this->discord->getHttpClient()->getDriver());
    }

    /**
     * Runs a request for an application given as a part or an id, or for the bot's own.
     *
     * @param Application|string|null            $application
     * @param callable(string): PromiseInterface $request     Given the application id.
     *
     * @return PromiseInterface
     */
    protected function forApplication($application, callable $request): PromiseInterface
    {
        $id = match (true) {
            $application instanceof Application => $application->id,
            is_string($application) => $application,
            default => $this->discord->application?->id,
        };

        if (null === $id || '' === $id) {
            return reject(new \DomainException('No application was given, and the bot\'s own is not known yet: wait for the client to be ready.'));
        }

        return $request((string) $id);
    }

    /**
     * Hydrates a list of parts into a collection.
     *
     * @param string       $class
     * @param object|array $response
     */
    protected function collect(string $class, $response): ExCollectionInterface
    {
        $collection = $this->discord->getCollectionClass()::for($class);

        foreach ((array) $response as $item) {
            $collection->pushItem($this->discord->getFactory()->part($class, (array) $item, true));
        }

        return $collection;
    }
}
