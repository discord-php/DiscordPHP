<?php

namespace Discord\Parts\Channel;

use Carbon\Carbon;
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
     * Acknowledges the message on Discord servers.
     *
     * @return boolean 
     */
    public function acknowledgeMessage()
    {
        Guzzle::post($this->replaceWithVars('channels/:channel_id/messages/:id/ack'));

        return true;
    }

    /**
     * Shortcut for acknowledgeMessage();
     *
     * @return boolean 
     */
    public function ackMessage()
    {
        return $this->acknowledgeMessage();
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
