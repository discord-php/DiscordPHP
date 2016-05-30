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

use Discord\Cache\Cache;
use Discord\Exceptions\FileNotFoundException;
use Discord\Exceptions\PasswordEmptyException;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Repository\GuildRepository;
use Discord\Repository\PrivateChannelRepository;
use Discord\Repository\UserRepository;

/**
 * The client is the main interface for the client. Most calls on the main class are forwarded here.
 *
 * @property string $id
 * @property string $username
 * @property string $password
 * @property string $email
 * @property bool   $verified
 * @property string $avatar
 * @property string $discriminator
 * @property bool   $bot
 */
class Client extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'username', 'password', 'email', 'verified', 'avatar', 'discriminator', 'bot'];

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
        $this->factory->create(User::class, 
            [
                'id'            => $this->id,
                'username'      => $this->username,
                'avatar'        => $this->attributes['avatar'],
                'discriminator' => $this->discriminator,
            ], true
        );
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
     * Returns the avatar ID for the client.
     *
     * @return string The avatar ID for the client.
     */
    public function getAvatarIDAttribute()
    {
        return $this->avatar;
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
