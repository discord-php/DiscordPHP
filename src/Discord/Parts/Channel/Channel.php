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
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Invite;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Permissions\ChannelPermission;
use Discord\Parts\User\Member;
use Illuminate\Support\Collection;

/**
 * A Channel can be either a text or voice channel on a Discord guild.
 */
class Channel extends Part
{
    const TYPE_TEXT  = 'text';

    const TYPE_VOICE = 'voice';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'name',
        'type',
        'topic',
        'guild_id',
        'position',
        'is_private',
        'last_message_id',
        'permission_overwrites',
        'messages',
        'message_count',
        'bitrate',
    ];

    /**
     * {@inheritdoc}
     */
    protected $uris = [
        'get'    => 'channels/:id',
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
            $allow = $this->partFactory->create(ChannelPermission::class);
        }

        if (is_null($deny)) {
            $deny = $this->partFactory->create(ChannelPermission::class);
        }

        $payload = [
            'id'    => $part->id,
            'type'  => $type,
            'allow' => $allow->perms,
            'deny'  => $deny->perms,
        ];

        $this->guzzle->put("channels/{$this->id}/permissions/{$part->id}", $payload);

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

        $this->guzzle->patch(
            "guilds/{$this->guild_id}/members/{$member}",
            [
                'channel_id' => $this->id,
            ]
        );

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
        if (is_null($this->guild_id)) {
            return;
        }

        $key = 'guild.'.$this->guild_id;
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $request = $this->guzzle->get('guilds/'.$this->guild_id);
        $guild   = $this->partFactory->create(Guild::class, $request, true);

        $this->cache->set($key, $guild);

        $this->attributes_cache['guild'] = $guild;

        return $guild;
    }

    /**
     * Creates an invite for the channel.
     *
     * @param int  $max_age   The time that the invite will be valid in seconds.
     * @param int  $max_uses  The amount of times the invite can be used.
     * @param bool $temporary Whether the invite is for temporary membership.
     * @param bool $xkcd      Whether to generate an XKCD invite.
     *
     * @return Invite The new invite that was created.
     */
    public function createInvite($max_age = 3600, $max_uses = 0, $temporary = false, $xkcd = false)
    {
        $request = $this->guzzle->post(
            $this->replaceWithVariables('channels/:id/invites'),
            [
                'validate' => null,

                'max_age'   => $max_age,
                'max_uses'  => $max_uses,
                'temporary' => $temporary,
                'xkcdpass'  => $xkcd,
            ]
        );

        $invite = $this->partFactory->create(Invite::class, $request, true);

        $this->cache->set("invite.{$invite->code}", $invite);

        return $invite;
    }

    /**
     * Returns the messages attribute.
     *
     * Note: This is only used for messages that have been
     * recieved while the WebSocket has been running. If you
     * want message history use the `message_history` attribute
     * which is non-cached.
     *
     * @return Collection A collection of messages.
     */
    public function getMessagesAttribute()
    {
        $key = 'channel.'.$this->id.'.messages';
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        return $this->cache->set($key, new Collection());
    }

    /**
     * Returns the message history attribute.
     *
     * @return Collection A collection of messages.
     */
    public function getMessageHistoryAttribute()
    {
        if ($this->message_count >= 100) {
            trigger_error('Requesting more messages than 100 will only return 100.');
        }

        $request  = $this->guzzle->get("channels/{$this->id}/messages?limit={$this->message_count}");
        $messages = [];

        foreach ($request as $index => $message) {
            $message = $this->partFactory->create(Message::class, $message, true);
            $this->cache->set("message.{$message->id}", $message);
            $messages[$index] = $message;
        }

        $messages = new Collection($messages);

        return $messages;
    }

    /**
     * Returns the channels invites.
     *
     * @return Collection A collection of invites.
     */
    public function getInvitesAttribute()
    {
        if ($invites = $this->cache->get("channel.{$this->id}.invites")) {
            return $invites;
        }

        $request = $this->guzzle->get($this->replaceWithVariables('channels/:id/invites'));
        $invites = [];

        foreach ($request as $index => $invite) {
            $invite = $this->partFactory->create(Invite::class, $invite, true);
            $this->cache->set("invites.{$invite->code}", $invite);
            $invites[$index] = $invite;
        }

        $invites = new Collection($invites);

        $this->cache->set("channel.{$this->id}.invites", $invites);

        return $invites;
    }

    /**
     * Gets the overwrites attribute.
     *
     * @return Collection The overwrites attribute.
     */
    public function getOverwritesAttribute()
    {
        if (isset($this->attributes_cache['overwrites'])) {
            return $this->attributes_cache['overwrites'];
        }

        if ($overwrites = $this->cache->get("channels.{$this->id}.overwrites")) {
            return $overwrites;
        }

        $overwrites = [];

        // Will return an empty collection when you don't have permission.
        if (is_null($this->attributes['permission_overwrites'])) {
            return new Collection();
        }

        foreach ($this->attributes['permission_overwrites'] as $index => $data) {
            $data               = (array) $data;
            $data['channel_id'] = $this->attributes['id'];
            $overwrites[$index] = $this->partFactory->create(Overwrite::class, $data, true);
        }

        $overwrites = new Collection($overwrites);

        $this->cache->set("channels.{$this->id}.overwrites", $overwrites);

        return $overwrites;
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

        $request = $this->guzzle->post(
            "channels/{$this->id}/messages",
            [
                'content' => $text,
                'tts'     => $tts,
            ]
        );

        $message = $this->partFactory->create(Message::class, $request, true);

        $this->cache->set("message.{$message->id}", $message);

        if (!$this->cache->has("channel.{$this->id}.messages")) {
            $this->getMessagesAttribute();
        }

        $this->messages->push($message);

        return $message;
    }

    /**
     * Sends a file to the channel if it is a text channel.
     *
     * @param string $filepath The path to the file to be sent.
     * @param string $filename The name to send the file as.
     *
     * @throws \Discord\Exceptions\FileNotFoundException Thrown when the file does not exist.
     *
     * @return Message|bool Either a Message if the request passed or false if it failed.
     */
    public function sendFile($filepath, $filename)
    {
        if ($this->type != self::TYPE_TEXT) {
            return false;
        }

        if (!file_exists($filepath)) {
            throw new FileNotFoundException("File does not exist at path {$filepath}.");
        }

        $request = $this->guzzle->sendFile($this, $filepath, $filename);
        $this->partFactory->create(Message::class, $request, true);

        $this->cache->set("message.{$message->id}", $message);

        if (!$this->cache->has("channel.{$this->id}.messages")) {
            $this->getMessagesAttribute();
        }

        $this->messages->push($message);

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

        $this->guzzle->post("channels/{$this->id}/typing");

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
            'name'     => $this->name,
            'topic'    => $this->topic,
            'position' => $this->position,
        ];
    }
}
