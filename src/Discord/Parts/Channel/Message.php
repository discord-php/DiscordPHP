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
use Discord\Cache\Cache;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\Deferred;

/**
 * A message which is posted to a Discord text channel.
 *
 * @property string       $id
 * @property string       $channel_id
 * @property string       $content
 * @property array|User[] $mentions
 * @property User         $author
 * @property bool         $mention_everyone
 * @property int          $timestamp
 * @property int|null     $edited_timestamp
 * @property bool         $tts
 * @property array        $attachments
 * @property array        $embeds
 * @property int|null     $nonce
 */
class Message extends Part
{
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
        'mention_roles',
    ];

    /**
     * Replies to the message.
     *
     * @param string $text The text to reply with.
     *
     * @return \React\Promise\Promise
     */
    public function reply($text)
    {
        return $this->channel->sendMessage("{$this->author}, {$text}");
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel The channel the message was sent in.
     */
    public function getChannelAttribute()
    {
        $deferred = new Deferred();

        if ($channel = $this->cache->get("channel.{$this->channel_id}")) {
            $deferred->resolve($channel);

            return $deferred->promise();
        }

        $this->http->get($this->replaceWithVariables('channels/:channel_id'))->then(function ($response) use ($deferred) {
            $channel = $this->factory->create(Channel::class, $response, true);
            $this->cache->set("channel.{$channel->id}", $channel);
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
        return \React\Promise\FulfilledPromise($this->factory->create(User::class,
            [
                'id' => $this->attributes['author']->id,
                'username' => $this->attributes['author']->username,
                'avatar' => $this->attributes['author']->avatar,
                'discriminator' => $this->attributes['author']->discriminator,
            ],
            true
        ));
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
            'content' => $this->content,
            'mentions' => $this->mentions,
            'tts' => $this->tts,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        return [
            'content' => $this->content,
            'mentions' => $this->mentions,
        ];
    }
}
