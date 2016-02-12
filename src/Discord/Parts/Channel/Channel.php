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

use Discord\Exceptions\FileNotFoundException;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Invite;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Permissions\ChannelPermission;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;

/**
 * A Channel can be either a text or voice channel on a Discord guild.
 */
class Channel extends Part
{
    const TYPE_TEXT = 'text';
    const TYPE_VOICE = 'voice';

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'name', 'type', 'topic', 'guild_id', 'position', 'is_private', 'last_message_id', 'permission_override', 'messages', 'message_count'];

    /**
     * {@inheritdoc}
     */
    protected $uris = [
        'get' => 'channels/:id',
        'create' => 'guilds/:guild_id/channels',
        'update' => 'channels/:id',
        'delete' => 'channels/:id',
    ];

    /**
     * Runs any extra construction tasks.
     *
     * @return void
     */
    protected function afterConstruct()
    {
        $this->message_count = 50;
    }

    /**
     * Sets a permission value to the channel.
     *
     * @param Member|Role     $part     Either a Member or Role, permissions will be set on it.
     * @param Permission|null $allow    The permissions that define what the Member/Role can do.
     * @param Permission|null $disallow The permissions that define what the Member/Role can't do.
     *
     * @return bool Whether the function succeeded or failed.
     */
    public function setPermissions($part, $allow = null, $deny = null)
    {
        if ($part instanceof Member) {
            $type = 'member';
        } elseif ($part instanceof Role) {
            $type = 'role';
        } else {
            return false;
        }

        if (is_null($allow)) {
            $allow = new ChannelPermission();
        }

        if (is_null($deny)) {
            $deny = new ChannelPermission();
        }

        $payload = [
            'id' => $part->id,
            'type' => $type,
            'allow' => $allow->perms,
            'deny' => $deny->perms,
        ];

        Guzzle::put("channels/{$this->id}/permissions/{$part->id}", $payload);

        return true;
    }

    /**
     * Moves a member to another voice channel.
     *
     * @param Member|int The member to move. (either a Member part or the member ID)
     *
     * @return bool Whether the move succeeded or failed.
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
            'channel_id' => $this->id,
        ]);

        // At the moment we are unable to check if the member
        // was moved successfully.

        return true;
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild that the Channel belongs to or null if we don't have the guild ID.
     */
    public function getGuildAttribute()
    {
        if (isset($this->attributes_cache['messages'])) {
            return $this->attributes_cache['messages'];
        }

        if (is_null($this->guild_id)) {
            return null;
        }

        $request = Guzzle::get("guilds/{$this->guild_id}");
        $guild = new Guild((array) $request, true);

        $this->attributes_cache['messages'] = $guild;

        return $guild;
    }

    /**
     * Creates an invite for the channel.
     *
     * @return Invite The new invite that was created.
     */
    public function createInvite()
    {
        $request = Guzzle::post($this->replaceWithVariables('channels/:id/invites'));

        return new Invite((array) $request, true);
    }

    /**
     * Returns the messages attribute.
     *
     * @return Collection A collection of messages.
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
     * @param string $text The text to send in the message.
     * @param bool   $tts  Whether the message should be sent with text to speech enabled.
     *
     * @return Message|bool Either a Message if the request passed or false if it failed.
     */
    public function sendMessage($text, $tts = false)
    {
        if ($this->type != self::TYPE_TEXT) {
            return false;
        }

        $request = Guzzle::post("channels/{$this->id}/messages", [
            'content' => $text,
            'tts' => $tts,
        ]);

        $message = new Message((array) $request, true);

        if (! isset($this->attributes_cache['messages'])) {
            $this->attributes_cache['messages'] = new Collection();
        }

        $this->attributes_cache['messages']->push($message);

        return $message;
    }

    /**
     * Sends a file to the channel if it is a text channel.
     *
     * @param string $filepath The path to the file to be sent.
     * @param string $filename The name to send the file as.
     *
     * @return Message|bool Either a Message if the request passed or false if it failed.
     *
     * @throws \Discord\Exceptions\FileNotFoundException Thrown when the file does not exist.
     */
    public function sendFile($filepath, $filename)
    {
        if ($this->type != self::TYPE_TEXT) {
            return false;
        }

        if (! file_exists($filepath)) {
            throw new FileNotFoundException("File does not exist at path {$filepath}.");
        }

        $guzzle = new GuzzleClient(['http_errors' => false, 'allow_redirects' => true]);
        $url = Guzzle::$base_url."/channels/{$this->id}/messages";

        $headers = [
            'User-Agent' => Guzzle::getUserAgent(),
            'authorization' => DISCORD_TOKEN,
        ];

        $done = false;
        $finalRes = null;

        while (! $done) {
            $response = $guzzle->request('post', $url, [
                'headers' => $headers,
                'multipart' => [[
                    'name' => 'file',
                    'contents' => fopen($filepath, 'r'),
                    'filename' => $filename,
                ]],
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

        if (! isset($this->attributes_cache['messages'])) {
            $this->attributes_cache['messages'] = new Collection();
        }

        $this->attributes_cache['messages']->push($message);

        return $message;
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @return bool Whether the request succeeded or failed.
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
     * @return string Either 'text' or 'voice'.
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
     * @return array The attributes that will be sent when this part is created.
     */
    public function getCreatableAttributes()
    {
        return [
            'name' => $this->name,
            'type' => $this->getChannelType(),
        ];
    }

    /**
     * Returns the attributes needed to edit.
     *
     * @return array The attributes that will be sent when this part is updated.
     */
    public function getUpdatableAttributes()
    {
        return [
            'name' => $this->name,
            'topic' => $this->topic,
        ];
    }
}
