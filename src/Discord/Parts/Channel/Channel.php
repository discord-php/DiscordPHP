<?php

namespace Discord\Parts\Channel;

use Discord\Exceptions\FileNotFoundException;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Invite;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as GuzzleClient;

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
     * Sets a permission value to the channel.
     *
     * @param Member|Role $part 
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
     * Moves a member to another voice channel.
     *
     * @param Member|int
     * @return boolean 
     */
    public function moveMember($member)
    {
        if ($this->type != self::TYPE_VOICE) {
            return false;
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        Guzzle::patch("guilds/{$this->guild_id}/members/{$member}", [
            'channel_id' => $this->id
        ]);

        // At the moment we are unable to check if the member
        // was moved successfully.

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

        return new Invite((array) $request, true);
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
            $messages[$index] = new Message((array) $message, true);
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

        $message = new Message((array) $request, true);

        if (!isset($this->attributes_cache['messages'])) {
            $this->attributes_cache['messages'] = new Collection();
        }

        $this->attributes_cache['messages']->push($message);

        return $message;
    }

    /**
     * Sends a file to the channel if it is a text channel.
     *
     * @param string $filepath
     * @param string $filename 
     * @return Message|boolean 
     */
    public function sendFile($filepath, $filename)
    {
        if ($this->type != self::TYPE_TEXT) {
            return false;
        }

        if (!file_exists($filepath)) {
            throw new FileNotFoundException("File does not exist at path {$filepath}.");
        }

        $guzzle = new GuzzleClient(['http_errors' => false, 'allow_redirects' => true]);
        $url = Guzzle::$base_url . "/channels/{$this->id}/messages";

        $headers = [
            'User-Agent' => Guzzle::getUserAgent(),
            'authorization' => DISCORD_TOKEN
        ];

        $done = false;
        $finalRes = null;

        while (!$done) {
            $response = $guzzle->request('post', $url, [
                'headers' => $headers,
                'multipart' => [[
                    'name' => 'file',
                    'contents' => fopen($filepath, 'r'),
                    'filename' => $filename
                ]]
            ]);
            
            // Rate limiting
            if ($response->getStatusCode() == 429) {
                $tts = $response->getHeader('Retry-After') * 1000;
                usleep($tts);
                continue;
            }

            // Not good!
            if ($response->getStatusCode() < 200 || $response->getStatusCode() > 226) {
                Guzzle::handleError($response->getStatusCode(), $response->getReasonPhrase());
                continue;
            }

            $done = true;
            $finalRes = $response;
        }

        $request = json_decode($finalRes->getBody());

        $message = new Message((array) $request, true);

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
