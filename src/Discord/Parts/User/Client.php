<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Discord\Exceptions\FileNotFoundException;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\OAuth\Application;
use Discord\Parts\OAuth\ApplicationRoleConnectionMetadata;
use Discord\Parts\Part;
use Discord\Repository\EmojiRepository;
use Discord\Repository\GuildRepository;
use Discord\Repository\LobbyRepository;
use Discord\Repository\PrivateChannelRepository;
use Discord\Repository\SoundRepository;
use Discord\Repository\UserRepository;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * The client is the main interface for the client. Most calls on the main class are forwarded here.
 *
 * @since 2.0.0
 *
 * @property string       $id            The unique identifier of the client.
 * @property string       $username      The username of the client.
 * @property string       $discriminator The unique discriminator of the client.
 * @property string|null  $global_name   The user's display name, if it is set. For bots, this is the application name.
 * @property ?string      $avatar        The avatar URL of the client.
 * @property string|null  $avatar_hash   The avatar hash of the client.
 * @property bool         $bot           Whether the client is a bot.
 * @property bool|null    $mfa_enabled   Whether the Bot owner has two factor enabled on their account.
 * @property bool|null    $verified      Whether the client has verified their email.
 * @property ?string|null $email         The email of the client.
 * @property int|null     $flags         The flags on a user's account.
 * @property int|null     $public_flags  The public flags on a user's account.
 * @property User         $user          The user instance of the client.
 * @property Application  $application   The OAuth2 application of the bot.
 *
 * @property ExCollectionInterface<Connection>|Connection[]|null $connections     The connection object that the user has attached.
 * @property ApplicationRoleConnection|null                      $role_connection The role connection object that the user has attached.
 *
 * @property EmojiRepository          $emojis
 * @property GuildRepository          $guilds
 * @property PrivateChannelRepository $private_channels
 * @property SoundRepository          $sounds
 * @property UserRepository           $users
 */
class Client extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'verified',
        'username',
        'public_flags',
        'mfa_enabled',
        'id',
        'flags',
        'email',
        'discriminator',
        'global_name',
        'bot',
        'avatar',

        // actual form
        'user',
        'application',

        // internal
        'connections',
        'role_connection',
    ];

    /**
     * @inheritDoc
     */
    protected $repositories = [
        'emojis' => EmojiRepository::class,
        'guilds' => GuildRepository::class,
        'lobbies' => LobbyRepository::class,
        'private_channels' => PrivateChannelRepository::class,
        'sounds' => SoundRepository::class,
        'users' => UserRepository::class,
    ];

    /**
     * Runs any extra construction tasks.
     */
    public function afterConstruct(): void
    {
        $this->application = $this->factory->part(Application::class, [], true);
        $this->getCurrentApplication()->then(function (Application $application) {
            $this->application = $application;
            $this->discord->setClient($this);
            $this->discord->emit('application-init', [$this->discord]);
        });
    }

    /**
     * Returns a list of connection objects.
     *
     * @return PromiseInterface<ExCollectionInterface<Connection>|Connection[]>
     *
     * @since 10.33.0
     */
    public function getCurrentUserConnections(bool $fresh = false): PromiseInterface
    {
        if ($fresh || ! isset($this->attributes['connections'])) {
            return $this->__getCurrentUserConnections();
        }

        return resolve($this->attributes['connections']);
    }

    /**
     * Returns a list of connection objects.
     * Requires the connections OAuth2 scope.
     *
     * @link https://discord.com/developers/docs/resources/user#get-current-user-guild-member
     *
     * @return PromiseInterface<ExCollectionInterface<Connection>|Connection[]>
     *
     * @since 10.33.0
     */
    protected function __getCurrentUserConnections(): PromiseInterface
    {
        return $this->http->get(Endpoint::USER_CURRENT_CONNECTIONS)->then(function ($response) {
            $collection = Collection::for(Connection::class);

            foreach ($response as $connection) {
                $collection->pushItem($this->factory->part(Connection::class, $connection, true));
            }

            $this->connections = $collection;

            return $collection;
        });
    }

    /**
     * Returns the application role connection for the user.
     *
     * @return PromiseInterface<ApplicationRoleConnection>
     *
     * @since 10.33.0
     */
    public function getCurrentUserApplicationRoleConnection(bool $fresh = false): PromiseInterface
    {
        if ($fresh || ! isset($this->attributes['role_connection'])) {
            return $this->__getCurrentUserApplicationRoleConnection();
        }

        return resolve($this->attributes['role_connection']);
    }

    /**
     * Returns the application role connection for the user.
     * Requires an OAuth2 access token with role_connections.write scope for the application specified in the path.
     *
     * @return PromiseInterface<ApplicationRoleConnection>
     *
     * @since 10.33.0
     */
    protected function __getCurrentUserApplicationRoleConnection(): PromiseInterface
    {
        return $this->http->get(Endpoint::USER_CURRENT_APPLICATION_ROLE_CONNECTION)
            ->then(fn ($response) => $this->role_connection = $this->factory->part(ApplicationRoleConnection::class, $response, true));
    }

    /**
     * Updates and returns the application role connection for the user.
     * Requires an OAuth2 access token with role_connections.write scope for the application specified in the path.
     *
     * @param ApplicationRoleConnection|array         $connection                      The connection data to update.
     * @param ?string|null                            $connection['platform_name']     The vanity name of the platform a bot has connected (max 50 characters).
     * @param ?string|null                            $connection['platform_username'] The username on the platform a bot has connected (max 100 characters).
     * @param ?ApplicationRoleConnectionMetadata|null $connection['metadata']          Object mapping application role connection metadata keys to their string-ified value (max 100 characters) for the user on the platform a bot has connected.
     *
     * @return PromiseInterface<ApplicationRoleConnection>
     *
     * @since 10.33.0
     */
    public function updateCurrentUserApplicationRoleConnection($connection)
    {
        if (! is_array($connection)) {
            $connection = $connection->jsonSerialize();
        }

        $allowed = ['platform_name', 'platform_username', 'metadata'];
        $params = array_filter(
            $connection,
            fn ($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($params)) {
            throw new \InvalidArgumentException('No valid parameters to update.');
        }

        return $this->http->put(Endpoint::USER_CURRENT_APPLICATION_ROLE_CONNECTION, $connection)
            ->then(fn ($response) => $this->role_connection = $this->factory->part(ApplicationRoleConnection::class, $response, true));
    }

    /**
     * Gets the current application of the client.
     *
     * @return PromiseInterface<Application>
     */
    public function getCurrentApplication(): PromiseInterface
    {
        return $this->http->get(Endpoint::APPLICATION_CURRENT)->then(function ($response) {
            $this->application->fill((array) $response);
            $this->created = true;

            return $this->application;
        });
    }

    /**
     * Updates the current application associated with the bot user.
     *
     * @link https://discord.com/developers/docs/resources/application#edit-current-application
     *
     * @param array $options Array of fields to update. All fields are optional.
     *
     * @return PromiseInterface<Application>
     */
    public function updateCurrentApplication(array $options): PromiseInterface
    {
        return $this->http->patch(Endpoint::APPLICATION_CURRENT, $options)->then(function ($response) {
            $this->application->fill((array) $response);
            $this->created = true;

            return $this->application;
        });
    }

    /**
     * Gets the user attribute.
     *
     * @return User
     */
    protected function getUserAttribute(): Part
    {
        return $this->factory->part(User::class, $this->attributes, true);
    }

    /**
     * Sets the users avatar.
     *
     * @param string $filepath The path to the file.
     *
     * @throws FileNotFoundException Thrown when the file does not exist.
     *
     * @return bool Whether the setting succeeded or failed.
     */
    public function setAvatar(string $filepath): bool
    {
        if (! file_exists($filepath)) {
            throw new FileNotFoundException("File does not exist at path {$filepath}.");
        }

        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        $file = file_get_contents($filepath);
        $base64 = base64_encode($file);

        $this->attributes['avatarhash'] = "data:image/{$extension};base64,{$base64}";

        return true;
    }

    /**
     * @return string The URL to the client's avatar.
     */
    protected function getAvatarAttribute(): string
    {
        return call_user_func_array([$this->user, 'getAvatarAttribute'], func_get_args());
    }

    /**
     * @return string|null The avatar hash for the client.
     */
    protected function getAvatarHashAttribute(): ?string
    {
        return $this->attributes['avatar'];
    }

    /**
     * Saves the client instance.
     *
     * @return PromiseInterface
     */
    public function save(?string $reason = null): PromiseInterface
    {
        return $this->http->patch(Endpoint::USER_CURRENT, $this->getUpdatableAttributes());
    }

    /**
     * @inheritDoc
     *
     * @link https://discord.com/developers/docs/resources/user#modify-current-user-json-params
     */
    public function getUpdatableAttributes(): array
    {
        $attr = [
            'username' => $this->attributes['username'],
        ];

        if (isset($this->attributes['avatarhash'])) {
            $attr['avatar'] = $this->attributes['avatarhash'];
        }

        return $attr;
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryAttributes(): array
    {
        return [];
    }
}
