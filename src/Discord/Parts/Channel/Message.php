<?php

namespace Discord\Parts\Channel;

use Carbon\Carbon;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\User\User;

class Message extends Part
{
    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['id', 'channel_id', 'content', 'mentions', 'author', 'mention_everyone', 'timestamp', 'edited_timestamp', 'tts', 'attachments', 'embeds', 'nonce'];
    
    /**
     * URIs used to get/create/update/delete the part.
     *
     * @var array 
     */
    protected $uris = [
        'get'       => 'channels/:channel_id/messages',
        'create'    => 'channels/:channel_id/messages',
        'update'    => 'channels/:channel_id/messages/:id',
        'delete'    => 'channels/:channel_id/messages/:id'
    ];

    /**
     * Replies to the message.
     *
     * @param string $text 
     * @return Message 
     */
    public function reply($text)
    {
        return $this->channel->sendMessage("{$this->author}, {$text}");
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel 
     */
    public function getChannelAttribute()
    {
        return new Channel([
            'id'    => $this->channel_id,
            'type'  => 'text'
        ], true);
    }

    /**
     * Returns the author attribute.
     *
     * @return User 
     */
    public function getAuthorAttribute()
    {
        return new User([
            'id'            => $this->attributes['author']->id,
            'username'      => $this->attributes['author']->username,
            'avatar'        => $this->attributes['author']->avatar,
            'discriminator' => $this->attributes['author']->discriminator
        ]);
    }

    /**
     * Returns the timestamp attribute.
     *
     * @return Carbon 
     */
    public function getTimestampAttribute()
    {
        return new Carbon($this->attributes['timestamp']);
    }

    /**
     * Returns the attributes needed to create.
     *
     * @return array 
     */
    public function getCreatableAttributes()
    {
        return [
            'content'   => $this->content,
            'mentions'  => $this->mentions,
            'tts'       => $this->tts
        ];
    }

    /**
     * Returns the attributes needed to edit.
     *
     * @return array 
     */
    public function getUpdatableAttributes()
    {
        return [
            'content'   => $this->content,
            'mentions'  => $this->mentions
        ];
    }
}
