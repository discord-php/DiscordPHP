<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Discord\Exceptions\FileNotFoundException;
use Discord\Parts\OAuth\Application;
use Discord\Parts\Part;
use Discord\Repository\GuildRepository;
use Discord\Repository\PrivateChannelRepository;
use Discord\Repository\UserRepository;
use Discord\Helpers\Deferred;
use React\Promise\ExtendedPromiseInterface;
use function React\Partial\bind as Bind;

/**
 * The client is the main interface for the client. Most calls on the main class are forwarded here.
 *
 * @property string                     $id            The unique identifier of the client.
 * @property string                     $username      The username of the client.
 * @property string                     $email         The email of the client.
 * @property bool                       $verified      Whether the client has verified their email.
 * @property string                     $avatar        The avatar URL of the client.
 * @property string                     $avatar_hash   The avatar hash of the client.
 * @property string                     $discriminator The unique discriminator of the client.
 * @property bool                       $bot           Whether the client is a bot.
 * @property User                       $user          The user instance of the client.
 * @property Application                $application   The OAuth2 application of the bot.
 * @property GuildRepository            $guilds
 * @property PrivateChannelRepository   $private_channels
 * @property UserRepository             $users
 */
class Client extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'username', 'email', 'verified', 'avatar', 'discriminator', 'bot', 'user', 'application'];

    /**
     * {@inheritdoc}
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
        $this->user = $this->factory->create(User::class,
            [
                'id' => $this->id,
                'username' => $this->username,
                'avatar' => $this->attributes['avatar'],
                'discriminator' => $this->discriminator,
            ], true
        );
        $this->application = $this->factory->create(Application::class, [], true);

        $this->http->get('oauth2/applications/@me')->done(function ($response) {
            $this->application->fill((array) $response);
        });
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
     * @return string The avatar hash for the client.
     */
    protected function getAvatarHashAttribute(): string
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
        $deferred = new Deferred();

        $this->http->patch('users/@me', $this->getUpdatableAttributes())->done(
            Bind([$deferred, 'resolve']),
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [];
    }
}
