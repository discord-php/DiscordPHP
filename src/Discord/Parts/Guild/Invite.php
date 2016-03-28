<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Carbon\Carbon;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * An invite to a Channel and Guild.
 */
class Invite extends Part
{
    /**
     * {@inheritdoc}
     */
    public $editable = false;

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['code', 'max_age', 'guild', 'revoked', 'created_at', 'temporary', 'uses', 'max_uses', 'inviter', 'xkcdpass', 'channel'];

    /**
     * {@inheritdoc}
     */
    protected $uris = [
        'get'    => 'invites/:code',
        'create' => 'channels/:channel_id/invites',
        'delete' => 'invite/:code',
    ];

    /**
     * Accepts the invite.
     *
     * @return bool Whether the accept succeeded.
     */
    public function accept()
    {
        if ($this->revoked) {
            return \React\Promise\reject(new \Exception('You cannot accept a revoked invite.'));
        }

        if ($this->uses >= $this->max_uses) {
            return \React\Promise\reject(new \Exception('This invite has been used more than it\'s max uses.'));
        }

        $deferred = new Deferred();

        $this->guzzle->post("invite/{$this->code}")->then(
            \React\Partial\bind_right($this->resolve, $deferred),
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Returns the invite URL attribute.
     *
     * @return string The URL to the invite.
     */
    public function getInviteUrlAttribute()
    {
        return "https://discord.gg/{$this->code}";
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild The Guild that you have been invited to.
     */
    public function getGuildAttribute()
    {
        return $this->partFactory->create(Guild::class, $this->attributes['guild'], true);
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel The Channel that you have been invited to.
     */
    public function getChannelAttribute()
    {
        return $this->partFactory->create(Channel::class, $this->attributes['channel'], true);
    }

    /**
     * Returns the channel id attribute.
     *
     * @return int The Channel ID that you have been invited to.
     */
    public function getChannelIdAttribute()
    {
        return $this->channel->id;
    }

    /**
     * Returns the inviter attribute.
     *
     * @return User The User that invited you.
     */
    public function getInviterAttribute()
    {
        return $this->partFactory->create(User::class, $this->attributes['inviter'], true);
    }

    /**
     * Returns the created at attribute.
     *
     * @return grbon The time that the invite was created.
     */
    public function getCreatedAtAttribute()
    {
        return new Carbon($this->attributes['created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes()
    {
        return [];
    }
}
