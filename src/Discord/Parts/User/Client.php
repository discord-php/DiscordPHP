<?php

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
use Discord\Http\Endpoint;
use Discord\Parts\OAuth\Application;
use Discord\Parts\Part;
use Discord\Repository\GuildRepository;
use Discord\Repository\PrivateChannelRepository;
use Discord\Repository\UserRepository;
use React\Promise\ExtendedPromiseInterface;

/**
 * The client is the main interface for the client. Most calls on the main class are forwarded here.
 *
 * @property string                   $id               The unique identifier of the client.
 * @property string                   $username         The username of the client.
 * @property string                   $discriminator    The unique discriminator of the client.
 * @property string                   $avatar           The avatar URL of the client.
 * @property string|null              $avatar_hash      The avatar hash of the client.
 * @property bool                     $bot              Whether the client is a bot.
 * @property bool|null                $mfa_enabled      Whether the Bot owner has two factor enabled on their account.
 * @property bool                     $verified         Whether the client has verified their email.
 * @property string|null              $email            The email of the client.
 * @property string|null              $flags            The flags on a user's account.
 * @property User                     $user             The user instance of the client.
 * @property Application              $application      The OAuth2 application of the bot.
 * @property GuildRepository          $guilds
 * @property PrivateChannelRepository $private_channels
 * @property UserRepository           $users
 */
class Client extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'verified',
        'username',
        'mfa_enabled',
        'id',
        'flags',
        'email',
        'discriminator',
        'bot',
        'avatar',
        'user',
        'application',
    ];

    /**
     * @inheritdoc
     */
    protected $repositories = [
        'guilds' => GuildRepository::class,
        'private_channels' => PrivateChannelRepository::class,
        'users' => UserRepository::class,
    ];

    /**
     * Runs any extra construction tasks.
     */
    public function afterConstruct(): void
    {
        $this->application = $this->factory->create(Application::class, [], true);

        $this->http->get(Endpoint::APPLICATION_CURRENT)->done(function ($response) {
            $this->application->fill((array) $response);
        });
    }

    /**
     * Gets the user attribute.
     *
     * @return User
     */
    protected function getUserAttribute(): Part
    {
        return $this->factory->create(User::class, $this->attributes, true);
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
     * @return string The URL to the clients avatar.
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
     * @return ExtendedPromiseInterface
     */
    public function save(): ExtendedPromiseInterface
    {
        return $this->http->patch(Endpoint::USER_CURRENT, $this->getUpdatableAttributes());
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        $attributes = [
            'username' => $this->attributes['username'],
        ];

        if (isset($this->attributes['avatarhash'])) {
            $attributes['avatar'] = $this->attributes['avatarhash'];
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [];
    }
}
