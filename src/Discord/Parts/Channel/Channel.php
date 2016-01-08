<?php

namespace Discord\Parts\Channel;

use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Invite;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

class Channel extends Part
{
    const TYPE_TEXT = 'text';
    const TYPE_VOICE = 'voice';

    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['id', 'name', 'type', 'topic', 'guild_id', 'position', 'is_private', 'last_message_id', 'permission_override', 'messages', 'message_count'];

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
     * Runs any extra construction tasks.
     *
     * @return void 
     */
    public function afterConstruct()
    {
        $this->message_count = 50;
    }

    /**
     * sets a permission value to the channel.
     *
     * @param User|Role $part 
     * @param Permission $allow 
     * @param Permission $disallow 
     * @return boolean 
     */
    public function setPermissions($part, $allow, $deny)
    {
        if ($part instanceof Member) {
            $type = 'member';
        } elseif ($part instanceof Role) {
            $type = 'role';
        } else {
            return false;
        }

        $payload = [
            'id' => $part->id,
            'type' => $type,
            'allow' => $allow->perms,
            'deny' => $deny->perms
        ];

        Guzzle::put("channels/{$this->id}/permissions/{$part->id}", $payload);

        return true;
    }

    /**
     * Creates an invite for the channel.
     *
     * @return Invite 
     */
    public function createInvite()
    {
        $request = Guzzle::post($this->replaceWithVariables('channels/:id/invites'));

        return new Invite([
            'code'          => $request->code,
            'max_age'       => $request->max_age,
            'guild'         => $request->guild,
            'revoked'       => $request->revoked,
            'created_at'    => $request->created_at,
            'temporary'     => $request->temporary,
            'uses'          => $request->uses,
            'max_uses'      => $request->max_uses,
            'inviter'       => $request->inviter,
            'xkcdpass'      => $request->xkcdpass,
            'channel'       => $request->channel
        ], true);
    }

    /**
     * Returns the messages attribute.
     *
     * @return Collection 
     */
    public function getMessagesAttribute()
    {
        if (isset($this->attributes_cache['messages'])) {
            return $this->attributes_cache['messages'];
        }

        if ($this->message_count >= 100) {
            trigger_error('Requesting more messages than 100 will only return 100.');
        }

        $request = Guzzle::get("channels/{$this->id}/messages?limit={$this->message_count}");
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
     * @param boolean $tts 
     * @return Message|boolean
     */
    public function sendMessage($text, $tts = false)
    {
        if ($this->type != self::TYPE_TEXT) {
            return false;
        }

        $request = Guzzle::post("channels/{$this->id}/messages", [
            'content'   => $text,
            'tts'       => $tts
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
