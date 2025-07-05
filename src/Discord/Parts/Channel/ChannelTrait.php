<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Carbon\Carbon;
use Discord\Builders\MessageBuilder;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Message\MessagePinData;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Permissions\RolePermission;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\WebSockets\Event;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

use function Discord\getSnowflakeTimestamp;
use function React\Promise\all;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Represents a guild or DM channel within Discord.
 *
 * @since 10.19.0
 *
 * @property      string                       $id                                 The ID of the channel or thread.
 * @property      int                          $type                               The type of channel or thread.
 * @property      string|null                  $guild_id                           The ID of the guild that the channel or thread belongs to. Only for text or voice channels.
 * @property-read Guild|null                   $guild                              The guild that the channel or thread belongs to. Only for text or voice channels.
 * @property      ?string|null                 $name                               The name of the channel or thread.
 * @property      ?string|null                 $last_message_id                    The unique identifier of the last message sent in the channel or thread. (may not point to an existing or valid message or thread).
 * @property      Carbon|null                  $last_pin_timestamp                 The timestamp when the last message was pinned in the channel or thread.
 * @property      int|null                     $rate_limit_per_user                Amount of seconds a user has to wait before sending a new message (slow mode).
 * @property      string|null                  $owner_id                           The ID of the DM creator (Only for DM or group channels) or the owner of the thread.
 * @property-read User|null                    $owner                              The DM creator or the owner of the thread.
 * @property-read Member|null                  $owner_member                       The member object for the DM creator or the owner of the thread.
 */
trait ChannelTrait
{
    /**
     * Returns the guild which the thread belongs to.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the last pinned message timestamp.
     *
     * @return Carbon|null
     *
     * @throws \Exception
     */
    protected function getLastPinTimestampAttribute(): ?Carbon
    {
        if (! isset($this->attributes['last_pin_timestamp'])) {
            return null;
        }

        return Carbon::parse($this->attributes['last_pin_timestamp']);
    }

    /**
     * Returns the owner of the thread.
     *
     * @return User|null
     */
    protected function getOwnerAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->owner_id);
    }

    /**
     * Returns the member object for the owner of the thread.
     *
     * @return Member|null
     */
    protected function getOwnerMemberAttribute(): ?Member
    {
        if ($guild = $this->guild) {
            return $guild->members->get('id', $this->owner_id);
        }

        return null;
    }

    /**
     * Returns the parent channel of the thread.
     *
     * @return Channel|null
     */
    protected function getParentAttribute(): ?Channel
    {
        if ($guild = $this->guild) {
            return $guild->channels->get('id', $this->parent_id);
        }

        return $this->discord->getChannel($this->parent_id);
    }

    /**
     * Gets the is_private attribute.
     *
     * @return bool Whether the channel is private.
     */
    protected function getIsPrivateAttribute(): bool
    {
        return in_array($this->type, [Channel::TYPE_DM, Channel::TYPE_GROUP_DM]);
    }

    /**
     * Returns if channel type is text based.
     *
     * @return bool Whether the channel is possible for sending text.
     */
    public function isTextBased(): bool
    {
        return in_array($this->type, [
            Channel::TYPE_GUILD_TEXT,
            Channel::TYPE_DM,
            Channel::TYPE_GUILD_VOICE,
            Channel::TYPE_GROUP_DM,
            Channel::TYPE_PUBLIC_THREAD,
            Channel::TYPE_PRIVATE_THREAD,
            Channel::TYPE_GUILD_ANNOUNCEMENT,
            Channel::TYPE_GUILD_STAGE_VOICE,
        ]);
    }


    /**
     * Returns if allow text.
     *
     * @return bool if we can send text or not.
     *
     * @deprecated 10.0.0 Use `Channel::isTextBased()`
     */
    public function allowText()
    {
        return $this->isTextBased();
    }

    /**
     * Returns if allow voice.
     *
     * @return bool if we can send voice or not.
     *
     * @deprecated 10.0.0 Use `Channel::isVoiceBased()`
     */
    public function allowVoice()
    {
        return $this->isVoiceBased();
    }

    /**
     * Returns if allow invite.
     *
     * @return bool if we can make invite or not.
     *
     * @deprecated 10.0.0 Use `Channel::canInvite()`
     */
    public function allowInvite()
    {
        return $this->canInvite();
    }

    /**
     * Returns if invite can be created in this type of channel.
     *
     * @return bool Whether the channel type is possible for creating invite.
     */
    public function canInvite(): bool
    {
        return in_array($this->type, [Channel::TYPE_GUILD_TEXT, Channel::TYPE_GUILD_VOICE, Channel::TYPE_GUILD_ANNOUNCEMENT, Channel::TYPE_GUILD_STAGE_VOICE, Channel::TYPE_GUILD_FORUM]);
    }

    /**
     * Returns if channel type is voice based.
     *
     * @return bool Whether the channel is possible for voice.
     */
    public function isVoiceBased(): bool
    {
        return in_array($this->type, [Channel::TYPE_GUILD_VOICE, Channel::TYPE_GUILD_STAGE_VOICE]);
    }
    /**
     * Returns the bot's permissions in the thread.
     *
     * @return RolePermission|null
     */
    public function getBotPermissions(): ?RolePermission
    {
        return $this->guild?->members->get('id', $this->discord->id)?->getPermissions($this);
    }

    /**
     * Fetches message history.
     *
     * @link https://discord.com/developers/docs/resources/channel#get-channel-messages
     *
     * @param array               $options           Array of options.
     * @param string|Message|null $options['around'] Get messages around this message ID.
     * @param string|Message|null $options['before'] Get messages before this message ID.
     * @param string|Message|null $options['after']  Get messages after this message ID.
     * @param int|null            $options['limit']  Max number of messages to return (1-100). Defaults to 50.
     *
     * @throws NoPermissionsException Missing `read_message_history` permission.
     *                                Or also missing `connect` permission for text in voice.
     * @throws \RangeException
     *
     * @return PromiseInterface<Collection<Message[]>>
     */
    public function getMessageHistory(array $options = []): PromiseInterface
    {
        if (! $this->is_private && $botperms = $this->getBotPermissions()) {
            if (! $botperms->read_message_history) {
                return reject(new NoPermissionsException("You do not have permission to read message history in the channel {$this->id}."));
            }

            if ($this->type == Channel::TYPE_GUILD_VOICE && ! $botperms->connect) {
                return reject(new NoPermissionsException("You do not have permission to connect in the channel {$this->id}."));
            }
        }

        $resolver = new OptionsResolver();
        $resolver->setDefaults(['limit' => 50, 'cache' => true]);
        $resolver->setDefined(['before', 'after', 'around']);
        $resolver->setAllowedTypes('before', [Message::class, 'string']);
        $resolver->setAllowedTypes('after', [Message::class, 'string']);
        $resolver->setAllowedTypes('around', [Message::class, 'string']);
        $resolver->setAllowedTypes('limit', 'integer');
        $resolver->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 100));

        $options = $resolver->resolve($options);
        if (isset($options['before'], $options['after']) ||
            isset($options['before'], $options['around']) ||
            isset($options['around'], $options['after'])) {
            return reject(new \RangeException('Can only specify one of before, after and around.'));
        }

        $endpoint = Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $this->id);
        $endpoint->addQuery('limit', $options['limit']);

        if (isset($options['before'])) {
            $endpoint->addQuery('before', $options['before'] instanceof Message ? $options['before']->id : $options['before']);
        }
        if (isset($options['after'])) {
            $endpoint->addQuery('after', $options['after'] instanceof Message ? $options['after']->id : $options['after']);
        }
        if (isset($options['around'])) {
            $endpoint->addQuery('around', $options['around'] instanceof Message ? $options['around']->id : $options['around']);
        }

        return $this->http->get($endpoint)->then(function ($responses) {
            $messages = Collection::for(Message::class);

            foreach ($responses as $response) {
                $messages->pushItem($this->messages->get('id', $response->id) ?: $this->messages->create($response, true));
            }

            return $messages;
        });
    }

    /**
     * Bulk deletes an array of messages.
     *
     * @link https://discord.com/developers/docs/resources/channel#bulk-delete-messages
     *
     * @param array|Traversable $messages An array of messages to delete.
     * @param string|null       $reason   Reason for Audit Log (only for bulk messages).
     *
     * @throws \InvalidArgumentException
     * @throws NoPermissionsException    Missing manage_messages permission.
     *
     * @return PromiseInterface
     */
    public function deleteMessages($messages, ?string $reason = null): PromiseInterface
    {
        if (! is_array($messages) && ! ($messages instanceof Traversable)) {
            return reject(new \InvalidArgumentException('$messages must be an array or implement Traversable.'));
        }

        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_messages) {
                return reject(new NoPermissionsException("You do not have permission to delete messages in the channel {$this->id}."));
            }
        }

        $headers = $promises = $messagesBulk = $messagesSingle = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        foreach ($messages as $message) {
            if ($message instanceof Message) {
                $message = $message->id;
            }

            if ($this->is_private || getSnowflakeTimestamp($message) < time() - 1209600) {
                $messagesSingle[] = $message;
            } else {
                $messagesBulk[] = $message;
            }
        }

        while (count($messagesBulk) > 1) {
            $promises[] = $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES_BULK_DELETE, $this->id), ['messages' => array_slice($messagesBulk, 0, 100)], $headers);
            $messagesBulk = array_slice($messagesBulk, 100);
        }

        $messagesSingle = array_merge($messagesSingle, $messagesBulk);

        foreach ($messagesSingle as $message) {
            $promises[] = $this->http->delete(Endpoint::bind(Endpoint::CHANNEL_MESSAGE, $this->id, $message));
        }

        return all($promises);
    }

    /**
     * Returns the channels pinned messages.
     *
     * @link https://discord.com/developers/docs/resources/message#get-channel-pins
     *
     * @param int                   $options['limit']  The amount of messages to retrieve.
     * @param Message|Carbon|string $options['before'] A message or timestamp to get messages before.
     *
     * @return PromiseInterface<Collection<MessagePinData>
     *
     * @since 10.19.0 Added $options parameter to allow for pagination.
    */
    public function getPinnedMessages(array $options = []): PromiseInterface
    {
        if ($this->guild_id && $botperms = $this->getBotPermissions()) {
            if (! $botperms->view_channel) {
                return reject(new NoPermissionsException("You do not have permission to view messages in the channel {$this->id}."));
            }
            //  If the user is missing the READ_MESSAGE_HISTORY permission in the channel, then no pins will be returned.
            if (! $botperms->read_message_history) {
                return resolve(Collection::for(Message::class));
            }
        }

        $resolver = new OptionsResolver();
        $resolver
            //->setDefaults(['limit' => 50])
            ->setDefined(['before', 'limit'])
            ->setAllowedTypes('before', [Carbon::class, 'string', 'null'])
            ->setAllowedTypes('limit', 'integer')
            ->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 50));

        $options = $resolver->resolve($options);

        $endpoint = Endpoint::bind(Endpoint::CHANNEL_MESSAGES_PINS, $this->id);

        if (isset($options['limit'])) {
            $endpoint->addQuery('limit', $options['limit']);
        }

        if (isset($options['before'])) {
            if ($options['before'] instanceof Message) {
                $options['before'] = $options['before']->timestamp;
            }
            if ($options['before'] instanceof Carbon) {
                $options['before'] = $options['before']->toIso8601String();
            }

            $endpoint->addQuery('before', $options['before']);
        }

        return $this->http->get($endpoint)
            ->then(fn ($responses) => $this->factory->create(MessagePinData::class, $responses));
    }

    /**
     * Pin a message in a channel.
     *
     * @link https://discord.com/developers/docs/resources/message#pin-message
     *
     * @param Message     $message The message to pin.
     * @param string|null $reason  Reason for Audit Log.
     * @param array       $options Additional options.
     *
     * @throws NoPermissionsException Missing manage_messages permission.
     * @throws \RuntimeException
     *
     * @return PromiseInterface<Message>
     *
     * @since 10.19.0 Updated endpoint to use the new pin message endpoint.
     */
    public function pinMessage(Message $message, ?string $reason = null): PromiseInterface
    {
        if (! in_array($this->type, [Channel::TYPE_DM, Channel::TYPE_GROUP_DM]) && $botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_messages) {
                return reject(new NoPermissionsException("You do not have permission to pin messages in the channel {$this->id}."));
            }
        }

        if ($message->pinned) {
            return reject(new \RuntimeException('This message is already pinned.'));
        }

        if ($message->channel_id != $this->id) {
            return reject(new \RuntimeException('You cannot pin a message to a different channel.'));
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->put(Endpoint::bind(Endpoint::CHANNEL_MESSAGES_PIN, $this->id, $message->id), null, $headers)->then(function () use (&$message) {
            $message->pinned = true;

            return $message;
        });
    }

    /**
     * Removes a message from the channels pinboard.
     *
     * @link https://discord.com/developers/docs/resources/message#unpin-message
     *
     * @param Message     $message The message to un-pin.
     * @param string|null $reason  Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing manage_messages permission.
     * @throws \RuntimeException
     *
     * @return PromiseInterface
     *
     * @since 10.19.0 Updated endpoint to use the new unpin message endpoint.
     */
    public function unpinMessage(Message $message, ?string $reason = null): PromiseInterface
    {
        if (! in_array($this->type, [Channel::TYPE_DM, Channel::TYPE_GROUP_DM]) && $botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_messages) {
                return reject(new NoPermissionsException("You do not have permission to unpin messages in the channel {$this->id}."));
            }
        }

        if (! $message->pinned) {
            return reject(new \RuntimeException('This message is not pinned.'));
        }

        if ($message->channel_id != $this->id) {
            return reject(new \RuntimeException('You cannot un-pin a message not sent in this thread.'));
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->delete(Endpoint::bind(Endpoint::CHANNEL_MESSAGES_PIN, $this->id, $message->id), null, $headers)->then(function () use (&$message) {
            $message->pinned = false;

            return $message;
        });
    }

    /**
     * Sends a message to the channel.
     *
     * Takes a `MessageBuilder` or content of the message for the first
     * parameter. If the first parameter is an instance of `MessageBuilder`, the
     * rest of the arguments are disregarded.
     *
     * @link https://discord.com/developers/docs/resources/channel#create-message
     *
     * @param MessageBuilder|string      $message          The message builder that should be converted into a message, or the string content of the message.
     * @param bool                       $tts              Whether the message is TTS.
     * @param Embed|array|null           $embed            An embed object or array to send in the message.
     * @param AllowedMentions|array|null $allowed_mentions Allowed mentions object for the message.
     * @param Message|null               $replyTo          Sends the message as a reply to the given message instance.
     *
     * @throws \RuntimeException
     * @throws NoPermissionsException Missing various permissions depending on the message body.
     *
     * @return PromiseInterface<Message>
     */
    public function sendMessage($message, bool $tts = false, $embed = null, $allowed_mentions = null, ?Message $replyTo = null): PromiseInterface
    {
        if (! $this->isTextBased()) {
            return reject(new \RuntimeException('You can only send messages to text channels.'));
        }

        if (! $this->is_private && $botperms = $this->getBotPermissions()) {
            if (! $botperms->send_messages) {
                return reject(new NoPermissionsException("You do not have permission to send messages in the channel {$this->id}."));
            }

            if ($message->getTts() && ! $botperms->send_tts_messages) {
                return reject(new NoPermissionsException("You do not have permission to send tts messages in the channel {$this->id}."));
            }

            if ($message->numFiles() > 0 && ! $botperms->attach_files) {
                return reject(new NoPermissionsException("You do not have permission to send files in the channel {$this->id}."));
            }
        }

        return (function () use ($message) {
            if ($message->requiresMultipart()) {
                $multipart = $message->toMultipart();

                return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $this->id), (string) $multipart, $multipart->getHeaders());
            }

            return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $this->id), $message);
        })()->then(function ($response) {
            if (! $messagePart = $this->messages->get('id', $response->id)) {
                $messagePart = $this->messages->create($response, true);
            }

            return $messagePart;
        });
    }

    /**
     * Sends an embed to the channel.
     *
     * @deprecated 10.0.0 Use `Channel::sendMessage` with `MessageBuilder::addEmbed()`
     *
     * @see Channel::sendMessage()
     *
     * @param Embed $embed Embed to send.
     *
     * @return PromiseInterface<Message>
     */
    public function sendEmbed(Embed $embed): PromiseInterface
    {
        return $this->sendMessage(MessageBuilder::new()
            ->addEmbed($embed));
    }

    /**
     * Sends a file to the channel.
     *
     * @deprecated 7.0.0 Use `Channel::sendMessage` to send files.
     *
     * @see Channel::sendMessage()
     *
     * @param string      $filepath The path to the file to be sent.
     * @param string|null $filename The name to send the file as.
     * @param string|null $content  Message content to send with the file.
     * @param bool        $tts      Whether to send the message with TTS.
     *
     * @return PromiseInterface<Message>
     */
    public function sendFile(string $filepath, ?string $filename = null, ?string $content = null, bool $tts = false): PromiseInterface
    {
        $builder = MessageBuilder::new()
            ->setTts($tts)
            ->addFile($filepath, $filename);

        if ($content) {
            $builder->setContent($content);
        }

        return $this->sendMessage($builder);
    }

    /**
     * Broadcasts that you are typing to the channel. Lasts for 5 seconds.
     *
     * @link https://discord.com/developers/docs/resources/channel#trigger-typing-indicator
     *
     * @throws \RuntimeException
     *
     * @return PromiseInterface
     */
    public function broadcastTyping(): PromiseInterface
    {
        if (! $this->isTextBased()) {
            return reject(new \RuntimeException('You cannot broadcast typing to a voice channel.'));
        }

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_TYPING, $this->id));
    }

    /**
     * Creates a message collector for the channel.
     *
     * @param callable $filter           The filter function. Returns true or false.
     * @param array    $options
     * @param int      $options['time']  Time in milliseconds until the collector finishes or false.
     * @param int      $options['limit'] The amount of messages allowed or false.
     *
     * @return PromiseInterface<Collection<Message[]>>
     */
    public function createMessageCollector(callable $filter, array $options = []): PromiseInterface
    {
        $deferred = new Deferred();
        $messages = new Collection([], null, null);
        $timer = null;

        $options = array_merge([
            'time' => false,
            'limit' => false,
        ], $options);

        $eventHandler = function (Message $message) use (&$eventHandler, $filter, $options, &$messages, &$deferred, &$timer) {
            if ($message->channel_id != $this->id) {
                return;
            }
            // Reject messages not in this channel
            $filterResult = call_user_func_array($filter, [$message]);

            if ($filterResult) {
                $messages->pushItem($message);

                if ($options['limit'] !== false && count($messages) >= $options['limit']) {
                    $this->discord->removeListener(Event::MESSAGE_CREATE, $eventHandler);
                    $deferred->resolve($messages);

                    if (null !== $timer) {
                        $this->discord->getLoop()->cancelTimer($timer);
                    }
                }
            }
        };

        $this->discord->on(Event::MESSAGE_CREATE, $eventHandler);

        if ($options['time'] !== false) {
            $timer = $this->discord->getLoop()->addTimer($options['time'] / 1000, function () use (&$eventHandler, &$messages, &$deferred) {
                $this->discord->removeListener(Event::MESSAGE_CREATE, $eventHandler);
                $deferred->resolve($messages);
            });
        }

        return $deferred->promise();
    }

}
