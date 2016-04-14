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
use Discord\Helpers\Guzzle;
use Discord\Parts\Part;
use Discord\Parts\User\User;

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
        if ($channel = Cache::get("channel.{$this->channel_id}")) {
            return $channel;
        }

        return new Channel(
            [
                'id'   => $this->channel_id,
                'type' => 'text',
            ], true
        );
    }

    /**
     * Returns the full channel attribute.
     *
     * @return Channel The channel the message was sent in with extra information.
     */
    public function getFullChannelAttribute()
    {
        if ($channel = Cache::get("channel.{$this->channel_id}")) {
            return $channel;
        }

        $request = Guzzle::get($this->replaceWithVariables('channels/:channel_id'));
        $channel = new Channel((array) $request, true);

        Cache::set("channel.{$channel->id}", $channel);

        return $channel;
    }

    /**
     * Returns the author attribute.
     *
     * @return User The User that sent the message.
     */
    public function getAuthorAttribute()
    {
        return new User(
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
