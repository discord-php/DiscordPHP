<?php

namespace Discord\Parts\Channel;

use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Message;
use Discord\Parts\Part;

class Channel extends Part
{
    const TYPE_TEXT = 'text';
    const TYPE_VOICE = 'voice';

    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['id', 'name', 'type', 'topic', 'guild_id', 'position', 'is_private', 'last_message_id', 'permission_override', 'messages'];

    /**
     * URIs used to get/create/update/delete the part.
     *
     * @var array 
     */
    protected $uris = [
        'get'       => 'channels/:id',
        'create'    => 'guilds/:guild_id/channels',
        'update'    => 'channels/:id',
        'delete'    => 'channels/:id'
    ];

    /**
     * Returns the messages attribute.
     *
     * @return array 
     */
    public function getMessagesAttribute()
    {
        if (isset($this->attributes_cache['messages'])) {
            return $this->attributes_cache['messages'];
        }

        $request = Guzzle::get("channels/{$this->id}/messages");
        $messages = [];

        foreach ($request as $index => $message) {
            $messages[$index] = new Message([
                'id'                => $message->id,
                'channel_id'        => $message->channel_id,
                'content'           => $message->content,
                'mentions'          => $message->mentions,
                'author'            => $message->author,
                'mention_everyone'  => $message->mention_everyone,
                'timestamp'         => $message->timestamp,
                'edited_timestamp'  => $message->edited_timestamp,
                'tts'               => $message->tts,
                'attachments'       => $message->attachments,
                'embeds'            => $message->embeds
            ], true);
        }

        $messages = new Collection($messages);

        $this->attributes_cache['messages'] = $messages;

        return $messages;
    }

    /**
     * Sends a message to the channel if it is a text channel.
     *
     * @param string $text 
     * @param array $mentions
     * @param boolean $tts 
     * @return Message|boolean
     */
    public function sendMessage($text, array $mentions = [], $tts = false)
    {
        if ($this->type != self::TYPE_TEXT) {
            return false;
        }

        $request = Guzzle::post("channels/{$this->id}/messages", [
            'content'    => $text,
            'mentions'    => $mentions,
            'tts'        => $tts
        ]);

        $message = new Message([
            'id'                => $request->id,
            'channel_id'        => $request->channel_id,
            'content'           => $request->content,
            'mentions'          => $request->mentions,
            'author'            => $request->author,
            'mention_everyone'  => $request->mention_everyone,
            'timestamp'         => $request->timestamp,
            'edited_timestamp'  => $request->edited_timestamp,
            'tts'               => $request->tts,
            'attachments'       => $request->attachments,
            'embeds'            => $request->embeds
        ], true);

        if (!isset($this->attributes_cache['messages'])) {
            $this->attributes_cache['messages'] = new Collection();
        }

        $this->attributes_cache['messages']->push($message);

        return $message;
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @return boolean 
     */
    public function broadcastTyping()
    {
        if ($this->type != self::TYPE_TEXT) {
            return false;
        }

        Guzzle::post("channels/{$this->id}/typing");

        return true;
    }

    /**
     * Returns the channel type.
     *
     * @return string 
     */
    public function getChannelType()
    {
        switch ($this->type) {
            case 'text':
            case 'voice':
                return $this->type;
                break;
            default:
                return 'text';
                break;
        }
    }

    /**
     * Returns the attributes needed to create.
     *
     * @return array 
     */
    public function getCreatableAttributes()
    {
        return [
            'name'  => $this->name,
            'type'  => $this->getChannelType()
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
            'name'  => $this->name,
            'topic' => $this->topic
        ];
    }
}
