<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Discord\Exceptions\FileNotFoundException;
use Discord\Exceptions\PasswordEmptyException;
use Discord\Parts\OAuth\Application;
use Discord\Parts\Part;
use Discord\Repository\GuildRepository;
use Discord\Repository\PrivateChannelRepository;
use Discord\Repository\UserRepository;
use React\Promise\Deferred;

/**
 * The client is the main interface for the client. Most calls on the main class are forwarded here.
 *
 * @property string                           $id            The unique identifier of the client.
 * @property string                           $username      The username of the client.
 * @property string                           $password      The password of the client (if they have provided it).
 * @property string                           $email         The email of the client.
 * @property bool                             $verified      Whether the client has verified their email.
 * @property string                           $avatar        The avatar URL of the client.
 * @property string                           $avatar_hash   The avatar hash of the client.
 * @property string                           $discriminator The unique discriminator of the client.
 * @property bool                             $bot           Whether the client is a bot.
 * @property \Discord\Parts\User\User         $user          The user instance of the client.
 * @property \Discord\Parts\OAuth\Application $application   The OAuth2 application of the bot.
 * @property \Discord\Repository\GuildRepository          $guilds
 * @property \Discord\Repository\PrivateChannelRepository $private_channels
 * @property \Discord\Repository\UserRepository           $users
 */
class Client extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'username', 'password', 'email', 'verified', 'avatar', 'discriminator', 'bot', 'user', 'application'];

    /**
     * {@inheritdoc}
     */
    protected $repositories = [
        'guilds'           => GuildRepository::class,
        'private_channels' => PrivateChannelRepository::class,
        'users'            => UserRepository::class,
    ];

    /**
     * Runs any extra construction tasks.
     *
     * @return void
     */
    public function afterConstruct()
    {
        $this->user = $this->factory->create(User::class,
            [
                'id'            => $this->id,
                'username'      => $this->username,
                'avatar'        => $this->attributes['avatar'],
                'discriminator' => $this->discriminator,
            ], true
        );
        $this->application = $this->factory->create(Application::class, [], true);

        $this->http->get('oauth2/applications/@me')->then(function ($response) {
            $this->application->fill((array) $response);
        });
    }

    /**
     * Sets the users avatar.
     *
     * @param string $filepath The path to the file.
     *
     * @throws \Discord\Exceptions\FileNotFoundException Thrown when the file does not exist.
     *
     * @return bool Whether the setting succeeded or failed.
     */
    public function setAvatar($filepath)
    {
        if (! file_exists($filepath)) {
            throw new FileNotFoundException("File does not exist at path {$filepath}.");
        }

        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        $file      = file_get_contents($filepath);
        $base64    = base64_encode($file);

        $this->attributes['avatarhash'] = "data:image/{$extension};base64,{$base64}";

        return true;
    }

    /**
     * Returns the avatar URL for the client.
     *
     * @return string The URL to the client's avatar.
     */
    public function getAvatarAttribute()
    {
        if (empty($this->attributes['avatar'])) {
            return;
        }

        return "https://discordapp.com/api/users/{$this->id}/avatars/{$this->attributes['avatar']}.jpg";
    }

    /**
     * Returns the avatar hash for the client.
     *
     * @return string The avatar hash for the client.
     */
    public function getAvatarHashAttribute()
    {
        return $this->attributes['avatar'];
    }

    /**
     * Saves the client instance.
     *
     * @return \React\Promise\Promise
     */
    public function save()
    {
        $deferred = new Deferred();

        $this->http->patch('users/@me', $this->getUpdatableAttributes())->then(
            \React\Partial\bind_right($this->resolve, $deferred),
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        $attributes = [
            'username' => $this->attributes['username'],
        ];

        if (isset($this->attributes['avatarhash'])) {
            $attributes['avatar'] = $this->attributes['avatarhash'];
        }

        if (! $this->bot) {
            if (empty($this->attributes['password'])) {
                throw new PasswordEmptyException('You must enter your password to update your profile.');
            }

            $attributes['email']    = $this->email;
            $attributes['password'] = $this->attributes['password'];

            if (! empty($this->attributes['new_password'])) {
                $attributes['new_password'] = $this->attributes['new_password'];
            }
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryAttributes()
    {
        return [];
    }
}
