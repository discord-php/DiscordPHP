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

use Discord\Cache\Cache;
use Discord\Exceptions\FileNotFoundException;
use Discord\Helpers\Collection;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Invite;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Permissions\ChannelPermission;
use Discord\Parts\User\Member;
use React\Promise\Deferred;

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
        'bitrate'
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
     * @return \React\Promise\Promise Whether the function succeeded or failed.
     */
    public function setPermissions($part, $allow = null, $deny = null)
    {
        if ($part instanceof Member) {
            $type = 'member';
        } elseif ($part instanceof Role) {
            $type = 'role';
        } else {
            return \React\Promise\reject(new \Exception('You can only set permissions on members and roles.'));
        }

        $deferred = new Deferred();

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

        $this->guzzle->put("channels/{$this->id}/permissions/{$part->id}", $payload)->then(
            \React\Partial\bind_right($this->resolve, $deferred),
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Moves a member to another voice channel.
     *
     * @param Member|int The member to move. (either a Member part or the member ID)
     *
     * @return \React\Promise\Promise Whether the move succeeded or failed.
     */
    public function moveMember($member)
    {
        if ($this->type != self::TYPE_VOICE) {
            return \React\Promise\reject(new \Exception('You cannot move a member to a text channel.'));
        }

        if ($member instanceof Member) {
            $member = $member->id;
        }

        $this->guzzle->patch(
            "guilds/{$this->guild_id}/members/{$member}",
            [
                'channel_id' => $this->id,
            ]
        )->then(
            \React\Partial\bind_right($this->resolve, $deferred),
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->resolve();
    }

    /**
     * Returns the guild attribute.
     *
     * @return \React\Promise\Promise The guild that the Channel belongs to or null if we don't have the guild ID.
     */
    public function getGuildAttribute()
    {
        if (is_null($this->guild_id)) {
            return \React\Promise\reject(new \Exception('No guild ID set.'));
        }

        if ($guild = Cache::get("guild.{$this->guild_id}")) {
            return \React\Promise\resolve($guild);
        }

        $deferred = new Deferred();

        $this->guzzle->get("guilds/{$this->guild_id}")->then(function ($response) use ($deferred) {
            $guild = $this->partFactory->create(Guild::class, $response, true);

            Cache::set("guild.{$guild->id}", $guild);
            $deferred->resolve($guild);
        }, \React\Partial\bind_right($this->reject, $deferred));

        return $deferred->promise();
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
        $deferred = new Deferred();

        $this->guzzle->post(
            $this->replaceWithVariables('channels/:id/invites'),
            [
                'validate' => null,

                'max_age'   => $max_age,
                'max_uses'  => $max_uses,
                'temporary' => $temporary,
                'xkcdpass'  => $xkcd,
            ]
        )->then(function ($response) use ($deferred) {
            $invite = $this->partFactory->create(Invite::class, $response, true);

            Cache::set("invite.{$invite->code}", $invite);
            $deferred->resolve($invite);
        }, \React\Partial\bind_right($this->reject, $deferred));

        return $deferred->promise();
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
        if (!Cache::get("channel.{$this->id}.messages")) {
            Cache::set("channel.{$this->id}.messages", new Collection([], "channel.{$this->id}.messages"));
        }

        return Cache::get("channel.{$this->id}.messages");
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

        $deferred = new Deferred();

        $this->guzzle->get("channels/{$this->id}/messages?limit={$this->message_count}")->then(function ($response) use ($deferred) {
            $messages = [];

            foreach ($response as $index => $message) {
                $message = $this->partFactory->create(Message::class, $message, true);
                Cache::set("message.{$message->id}", $message);
                $messages[$index] = $message;
            }

            $deferred->resolve(new Collection($messages));
        }, \React\Partial\bind_right($this->reject, $deferred));

        return $messages;
    }

    /**
     * Returns the channels invites.
     *
     * @return Collection A collection of invites.
     */
    public function getInvitesAttribute()
    {
        if ($invites = Cache::get("channel.{$this->id}.invites")) {
            return \React\Promise\resolve($invites);
        }

        $deferred = new Deferred();

        $this->guzzle->get($this->replaceWithVariables('channels/:id/invites'))->then(function ($response) use ($deferred) {
            $invites = [];

            foreach ($request as $index => $invite) {
                $invite = $this->partFactory->create(Invite::class, $invite, true);
                Cache::set("invites.{$invite->code}", $invite);
                $invites[$index] = $invite;
            }

            $invites = new Collection($invites);
            $invites->setCacheKey("channel.{$this->id}.invites", true);

            $deferred->resolve($invites);
        }, \React\Partial\bind_right($this->reject, $deferred));

        return $deferred->promise();
    }

    /**
     * Gets the overwrites attribute.
     *
     * @return Collection The overwrites attribute.
     */
    public function getOverwritesAttribute()
    {
        if (isset($this->attributes_cache['overwrites'])) {
            return \React\Promise\resolve($this->attributes_cache['overwrites']);
        }

        if ($overwrites = Cache::get("channels.{$this->id}.overwrites")) {
            return \React\Promise\resolve($overwrites);
        }

        $overwrites = [];

        // Will return an empty collection when you don't have permission.
        if (is_null($this->attributes['permission_overwrites'])) {
            return \React\Promise\resolve(new Collection([], "channels.{$this->id}.overwrites"));
        }

        foreach ($this->attributes['permission_overwrites'] as $index => $data) {
            $data               = (array) $data;
            $data['channel_id'] = $this->attributes['id'];
            $overwrites[$index] = $this->partFactory->create(Overwrite::class, $data, true);
        }

        $overwrites = new Collection($overwrites);
        $overwrites->setCacheKey("channels.{$this->id}.overwrites", true);

        return \React\Promise\resolve($overwrites);
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
            return \React\Promise\reject(new \Exception('You cannot send a message to a voice channel.'));
        }

        $deferred = new Deferred();

        $this->guzzle->post(
            "channels/{$this->id}/messages",
            [
                'content' => $text,
                'tts'     => $tts,
            ]
        )->then(function ($response) use ($deferred) {
            $message = $this->partFactory->create(Message::class, $response, true);
            Cache::set("message.{$message->id}", $message);
            $this->messages->push($message);

            $deferred->resolve($message);
        }, \React\Partial\bind_right($this->reject, $deferred));

        return $deferred->promise();
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
            return \React\Promise\reject(new \Exception('You cannot send a file to a voice channel.'));
        }

        $deferred = new Deferred();

        $this->guzzle->sendFile($this, $filepath, $filename)->then(function ($response) use ($deferred) {
            $message = $this->partFactory->create(Message::class, $response, true);

            Cache::set("message.{$message->id}", $message);

            if (!Cache::has("channel.{$this->id}.messages")) {
                $this->getMessagesAttribute();
            }

            $this->messages->push($message);
        }, \React\Partial\bind_right($this->reject, $deferred));

        return $deferred->promise();
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @return bool Whether the request succeeded or failed.
     */
    public function broadcastTyping()
    {
        if ($this->type != self::TYPE_TEXT) {
            return \React\Promise\reject(new \Exception('You cannot broadcast typing to a voice channel.'));
        }

        $deferred = new Deferred();

        $this->guzzle->post("channels/{$this->id}/typing")->then(
            \React\Partial\bind_right($this->resolve, $deferred),
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
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
