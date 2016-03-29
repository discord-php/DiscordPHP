<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Carbon\Carbon;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\Deferred;
use React\Promise\Promise;

/**
 * A message which is posted to a Discord text channel.
 */
class Message extends Part
{
    /**
     * {@inheritdoc}
     */
    public $findable = false;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'channel_id',
        'content',
        'mentions',
        'author',
        'mention_everyone',
        'timestamp',
        'edited_timestamp',
        'tts',
        'attachments',
        'embeds',
        'nonce',
    ];

    /**
     * {@inheritdoc}
     */
    protected $uris = [
        'get'    => 'channels/:channel_id/messages/:id',
        'create' => 'channels/:channel_id/messages',
        'update' => 'channels/:channel_id/messages/:id',
        'delete' => 'channels/:channel_id/messages/:id',
    ];

    /**
     * Replies to the message.
     *
     * @param string $text The text to reply with.
     *
     * @return Message A Message part that contains information about the message sent.
     */
    public function reply($text)
    {
        return $this->channel->sendMessage("{$this->author}, {$text}");
    }

    /**
     * Returns the channel attribute.
     *
     * Note: This channel object does not have a guild_id attribute, therfore you cannot get the guild from this
     * object. If you neeed the guild, use the `full_channel` attribute on the channel.
     *
     * @return Channel The channel the message was sent in.
     */
    public function getChannelAttribute()
    {
        if ($channel = $this->cache->get("channels.{$this->channel_id}")) {
            return $channel;
        }

        return $this->partFactory->create(
            Channel::class,
            [
                'id'   => $this->channel_id,
                'type' => 'text',
            ],
            true
        );
    }

    /**
     * Returns the full channel attribute.
     *
     * @return Promise Promise of the full channel attribute
     */
    public function getFullChannelAttribute()
    {
        if ($channel = $this->cache->get("channels.{$this->channel_id}")) {
            return \React\Promise\resolve($channel);
        }

        $deferred = new Deferred();

        $this->http->get($this->replaceWithVariables('channels/:channel_id'))->then(function ($response) use ($deferred) {
            $channel = $this->partFactory->create(Channel::class, $response, true);

            $this->cache->set("channels.{$channel->id}", $channel);
            $deferred->resolve($channel);
        }, \React\Partial\bind_right($this->reject, $deferred));

        return $deferred->promise();
    }

    /**
     * Returns the author attribute.
     *
     * @return User The User that sent the message.
     */
    public function getAuthorAttribute()
    {
        return $this->partFactory->create(
            User::class,
            [
                'id'            => $this->attributes['author']->id,
                'username'      => $this->attributes['author']->username,
                'avatar'        => $this->attributes['author']->avatar,
                'discriminator' => $this->attributes['author']->discriminator,
            ]
        );
    }

    /**
     * Returns the timestamp attribute.
     *
     * @return Carbon The time that the message was sent.
     */
    public function getTimestampAttribute()
    {
        return new Carbon($this->attributes['timestamp']);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes()
    {
        return [
            'content'  => $this->content,
            'mentions' => $this->mentions,
            'tts'      => $this->tts,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        return [
            'content'  => $this->content,
            'mentions' => $this->mentions,
        ];
    }
}
