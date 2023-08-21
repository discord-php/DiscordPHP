<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Thread;

use Carbon\Carbon;
use Discord\Builders\MessageBuilder;
use Discord\Helpers\Collection;
use Discord\Helpers\Deferred;
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\Permissions\RolePermission;
use Discord\Parts\Thread\Member as ThreadMember;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\Channel\MessageRepository;
use Discord\Repository\Thread\MemberRepository;
use Discord\WebSockets\Event;
use React\Promise\ExtendedPromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

use function Discord\getSnowflakeTimestamp;
use function React\Promise\all;
use function React\Promise\reject;

/**
 * Represents a Discord thread.
 *
 * @link https://discord.com/developers/docs/topics/threads
 *
 * @since 7.0.0
 *
 * @property      string        $id                    The ID of the thread.
 * @property      int           $type                  The type of thread.
 * @property      string        $guild_id              The ID of the guild which the thread belongs to.
 * @property-read Guild|null    $guild                 The guild which the thread belongs to.
 * @property      string        $name                  The name of the thread.
 * @property      string        $last_message_id       The ID of the last message sent in the thread.
 * @property      Carbon|null   $last_pin_timestamp    The timestamp when the last message was pinned in the thread.
 * @property      int           $rate_limit_per_user   Amount of seconds a user has to wait before sending a new message.
 * @property      string        $owner_id              The ID of the owner of the thread.
 * @property-read User|null     $owner                 The owner of the thread.
 * @property-read Member|null   $owner_member          The member object for the owner of the thread.
 * @property      string        $parent_id             The ID of the channel which the thread was started in.
 * @property-read Channel|null  $parent                The channel which the thread was created in.
 * @property      int           $message_count         Number of messages (not including the initial message or deleted messages) in a thread (if the thread was created before July 1, 2022, the message count is inaccurate when it's greater than 50).
 * @property      int           $member_count          An approximate count of the number of members in the thread. Stops counting at 50.
 * @property      object        $thread_metadata       Thread-specific fields not needed by other channels.
 * @property      bool          $archived              Whether the thread has been archived.
 * @property      int|null      $auto_archive_duration The number of minutes of inactivity until the thread is automatically archived.
 * @property      Carbon        $archive_timestamp     The time that the thread's archive status was changed.
 * @property      bool          $locked                Whether the thread has been locked.
 * @property      bool|null     $invitable             Whether non-moderators can add other non-moderators to a thread; only available on private threads.
 * @property      Carbon|null   $create_timestamp      Timestamp when the thread was created; only populated for threads created after 2022-01-09.
 * @property      int|null      $total_message_sent    Number of messages ever sent in a thread, it's similar to `message_count` on message creation, but will not decrement the number when a message is deleted.
 * @property      int|null      $flags                 Channel flags combined as a bitfield. PINNED can only be set for threads in forum channels.
 * @property      string[]|null $applied_tags          The IDs of the set of tags that have been applied to a thread in a forum channel, limited to 5.
 *
 * @property MessageRepository $messages Repository of messages sent in the thread.
 * @property MemberRepository  $members  Repository of members in the thread.
 *
 * @method ExtendedPromiseInterface<Message> sendMessage(MessageBuilder $builder)
 */
class Thread extends Part
{
    public const FLAG_PINNED = (1 << 1);

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'type',
        'guild_id',
        'name',
        'last_message_id',
        'last_pin_timestamp',
        'rate_limit_per_user',
        'owner_id',
        'parent_id',
        'message_count',
        'member_count',
        'thread_metadata',
        'member',
        'total_message_sent',
        'flags',
        'applied_tags',
    ];

    /**
     * {@inheritDoc}
     */
    protected $hidden = [
        'member',
    ];

    /**
     * {@inheritDoc}
     */
    protected $repositories = [
        'messages' => MessageRepository::class,
        'members' => MemberRepository::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected function afterConstruct(): void
    {
        if (isset($this->attributes['member'])) {
            $memberPart = $this->members->create((array) $this->attributes['member'] + [
                'id' => $this->id,
                'user_id' => $this->discord->id,
                'guild_id' => $this->guild_id,
            ], $this->created);
            $memberPart->created = &$this->created;
            $this->members->pushItem($memberPart);
        }
    }

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
     * Returns the timestamp when the last message was pinned in the thread.
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

        return new Carbon($this->attributes['last_pin_timestamp']);
    }

    /**
     * Returns whether the thread is archived.
     *
     * @return bool
     */
    protected function getArchivedAttribute(): bool
    {
        return $this->thread_metadata->archived ?? false;
    }

    /**
     * Returns whether the thread has been locked.
     *
     * @return bool
     */
    protected function getLockedAttribute(): bool
    {
        return $this->thread_metadata->locked ?? false;
    }

    /**
     * Returns whether the thread is archived.
     *
     * @return bool|null
     */
    protected function getInvitableAttribute(): ?bool
    {
        return $this->thread_metadata->invitable ?? null;
    }

    /**
     * Returns the number of minutes of inactivity required for the thread to
     * auto archive.
     *
     * @return int|null
     */
    protected function getAutoArchiveDurationAttribute(): ?int
    {
        return $this->thread_metadata->auto_archive_duration ?? null;
    }

    /**
     * Set whether the thread is archived.
     *
     * @param bool $value
     */
    protected function setArchivedAttribute(bool $value)
    {
        $this->attributes['thread_metadata']->archived = $value;
    }

    /**
     * Set whether the thread is locked.
     *
     * @param bool $value
     */
    protected function setLockedAttribute(bool $value)
    {
        $this->attributes['thread_metadata']->locked = $value;
    }

    /**
     * Set the number of minutes of inactivity required for the thread to auto
     * archive.
     *
     * @param int $value
     */
    protected function setAutoArchiveDurationAttribute(int $value)
    {
        $this->attributes['thread_metadata']->auto_archive_duration = $value;
    }

    /**
     * Returns the time that the thread's archive status was changed.
     *
     * Note that this does not mean the time that the thread was archived - it
     * can also mean the time when the thread was created, archived, unarchived
     * etc.
     *
     * @return Carbon
     *
     * @throws \Exception
     */
    protected function getArchiveTimestampAttribute(): Carbon
    {
        return new Carbon($this->thread_metadata->archive_timestamp);
    }

    /**
     * Returns the timestamp when the thread was created; only populated for
     * threads created after 2022-01-09.
     *
     * @return Carbon|null
     *
     * @throws \Exception
     */
    protected function getCreateTimestampAttribute(): ?Carbon
    {
        if (! isset($this->attributes['create_timestamp'])) {
            return null;
        }

        return new Carbon($this->thread_metadata->create_timestamp);
    }

    /**
     * Attempts to join the thread.
     *
     * @link https://discord.com/developers/docs/resources/channel#join-thread
     *
     * @return ExtendedPromiseInterface
     */
    public function join(): ExtendedPromiseInterface
    {
        return $this->http->put(Endpoint::bind(Endpoint::THREAD_MEMBER_ME, $this->id));
    }

    /**
     * Attempts to add a user to the thread.
     *
     * @link https://discord.com/developers/docs/resources/channel#add-thread-member
     *
     * @param User|Member|string $user User to add. Can be one of the user objects or a user ID.
     *
     * @return ExtendedPromiseInterface
     */
    public function addMember($user): ExtendedPromiseInterface
    {
        if ($user instanceof User || $user instanceof Member) {
            $user = $user->id;
        }

        return $this->http->put(Endpoint::bind(Endpoint::THREAD_MEMBER, $this->id, $user));
    }

    /**
     * Attempts to leave the thread.
     *
     * @link https://discord.com/developers/docs/resources/channel#leave-thread
     *
     * @return ExtendedPromiseInterface
     */
    public function leave(): ExtendedPromiseInterface
    {
        return $this->http->delete(Endpoint::bind(Endpoint::THREAD_MEMBER_ME, $this->id));
    }

    /**
     * Attempts to remove a user from the thread.
     *
     * @link https://discord.com/developers/docs/resources/channel#remove-thread-member
     *
     * @param User|Member|ThreadMember|string $user User to remove. Can be one of the user objects or a user ID.
     *
     * @return ExtendedPromiseInterface
     */
    public function removeMember($user): ExtendedPromiseInterface
    {
        if ($user instanceof User || $user instanceof Member) {
            $user = $user->id;
        } elseif ($user instanceof ThreadMember) {
            $user = $user->user_id;
        }

        return $this->http->delete(Endpoint::bind(Endpoint::THREAD_MEMBER, $this->id, $user));
    }

    /**
     * Rename the thread.
     *
     * @param string      $name   New thread name.
     * @param string|null $reason Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface<Thread>
     */
    public function rename(string $name, ?string $reason = null): ExtendedPromiseInterface
    {
        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::THREAD, $this->id), ['name' => $name], $headers)
            ->then(function ($response) {
                $this->name = $response->name;

                return $this;
            });
    }

    /**
     * Archive the thread.
     *
     * @param string|null $reason Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface<Thread>
     */
    public function archive(?string $reason = null): ExtendedPromiseInterface
    {
        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::THREAD, $this->id), ['archived' => true], $headers)
            ->then(function ($response) {
                $this->archived = $response->thread_metadata->archived;

                return $this;
            });
    }

    /**
     * Unarchive the thread.
     *
     * @param string|null $reason Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface<Thread>
     */
    public function unarchive(?string $reason = null): ExtendedPromiseInterface
    {
        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::THREAD, $this->id), ['archived' => false], $headers)
            ->then(function ($response) {
                $this->archived = $response->thread_metadata->archived;

                return $this;
            });
    }

    /**
     * Set auto archive duration of the thread.
     *
     * @param int         $duration Duration in minutes.
     * @param string|null $reason   Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface<Thread>
     */
    public function setAutoArchiveDuration(int $duration, ?string $reason = null): ExtendedPromiseInterface
    {
        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::THREAD, $this->id), ['auto_archive_duration' => $duration], $headers)
            ->then(function ($response) {
                $this->auto_archive_duration = $response->thread_metadata->auto_archive_duration;

                return $this;
            });
    }

    /**
     * Returns the thread's pinned messages.
     *
     * @link https://discord.com/developers/docs/resources/channel#get-pinned-messages
     *
     * @return ExtendedPromiseInterface<Collection<Message>>
     *
     * @todo Make it in a trait along with Channel
     */
    public function getPinnedMessages(): ExtendedPromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::CHANNEL_PINS, $this->id))
            ->then(function ($responses) {
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
     * @return ExtendedPromiseInterface
     *
     * @todo Make it in a trait along with Channel
     */
    public function deleteMessages($messages, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! is_array($messages) && ! ($messages instanceof Traversable)) {
            return reject(new \InvalidArgumentException('$messages must be an array or implement Traversable.'));
        }

        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_messages) {
                return reject(new NoPermissionsException("You do not have permission to delete messages in the thread {$this->id}."));
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

            if (getSnowflakeTimestamp($message) < time() - 1209600) {
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
     * Fetches the message history of the thread with a given array of arguments.
     *
     * @link https://discord.com/developers/docs/resources/channel#get-channel-messages
     *
     * @param array               $options           Array of options.
     * @param string|Message|null $options['around'] Get messages around this message ID.
     * @param string|Message|null $options['before'] Get messages before this message ID.
     * @param string|Message|null $options['after']  Get messages after this message ID.
     * @param int|null            $options['limit']  Max number of messages to return (1-100). Defaults to 50.
     *
     * @return ExtendedPromiseInterface<Collection<Message>>
     *
     * @todo Make it in a trait along with Channel
     */
    public function getMessageHistory(array $options = []): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults(['limit' => 50, 'cache' => false])
            ->setDefined(['before', 'after', 'around'])
            ->setAllowedTypes('before', [Message::class, 'string'])
            ->setAllowedTypes('after', [Message::class, 'string'])
            ->setAllowedTypes('around', [Message::class, 'string'])
            ->setAllowedTypes('limit', 'integer')
            ->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 100));

        $options = $resolver->resolve($options);

        if (isset($options['before'], $options['after']) ||
            isset($options['before'], $options['around']) ||
            isset($options['around'], $options['after'])) {
            return reject(new \Exception('Can only specify one of before, after and around.'));
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
                if (! $message = $this->messages->get('id', $response->id)) {
                    $message = $this->messages->create($response, true);
                    $this->messages->pushItem($message);
                }

                $messages->pushItem($message);
            }

            return $messages;
        });
    }

    /**
     * Pins a message in the thread.
     *
     * @link https://discord.com/developers/docs/resources/channel#pin-message
     *
     * @param Message     $message
     * @param string|null $reason  Reason for Audit Log.
     *
     * @throws \RuntimeException
     *
     * @return ExtendedPromiseInterface<Message>
     *
     * @todo Make it in a trait along with Channel
     */
    public function pinMessage(Message $message, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($message->pinned) {
            return reject(new \RuntimeException('This message is already pinned.'));
        }

        if ($message->channel_id != $this->id) {
            return reject(new \RuntimeException('You cannot pin a message not sent in this thread.'));
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->put(Endpoint::bind(Endpoint::CHANNEL_PIN, $this->id, $message->id), null, $headers)->then(function () use (&$message) {
            $message->pinned = true;

            return $message;
        });
    }

    /**
     * Unpins a message in the thread.
     *
     * @link https://discord.com/developers/docs/resources/channel#unpin-message
     *
     * @param Message     $message
     * @param string|null $reason  Reason for Audit Log.
     *
     * @throws \RuntimeException
     *
     * @return ExtendedPromiseInterface<Message>
     *
     * @todo Make it in a trait along with Channel
     */
    public function unpinMessage(Message $message, ?string $reason = null): ExtendedPromiseInterface
    {
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

        return $this->http->delete(Endpoint::bind(Endpoint::CHANNEL_PIN, $this->id, $message->id), null, $headers)->then(function () use (&$message) {
            $message->pinned = false;

            return $message;
        });
    }

    /**
     * Sends a message to the thread.
     *
     * Takes a `MessageBuilder` or content of the message for the first
     * parameter. If the first parameter is an instance of `MessageBuilder`, the
     * rest of the arguments are disregarded.
     *
     * @link https://discord.com/developers/docs/resources/channel#create-message
     *
     * @param MessageBuilder|string $message          The message builder that should be converted into a message, or the string content of the message.
     * @param bool                  $tts              Whether the message is TTS.
     * @param Embed|array|null      $embed            An embed object or array to send in the message.
     * @param array|null            $allowed_mentions Allowed mentions object for the message.
     * @param Message|null          $replyTo          Sends the message as a reply to the given message instance.
     *
     * @return ExtendedPromiseInterface<Message>
     *
     * @todo Make it in a trait along with Channel
     */
    public function sendMessage($message, bool $tts = false, $embed = null, $allowed_mentions = null, ?Message $replyTo = null): ExtendedPromiseInterface
    {
        // Backwards compatible support for old `sendMessage` function signature.
        if (! ($message instanceof MessageBuilder)) {
            $message = MessageBuilder::new()
                ->setContent($message);

            if ($tts) {
                $message->setTts(true);
            }

            if ($embed) {
                $message->addEmbed($embed);
            }

            if ($allowed_mentions) {
                $message->setAllowedMentions($allowed_mentions);
            }

            if ($replyTo) {
                $message->setReplyTo($replyTo);
            }
        }

        return (function () use ($message) {
            if ($message->requiresMultipart()) {
                $multipart = $message->toMultipart();

                return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $this->id), (string) $multipart, $multipart->getHeaders());
            }

            return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES, $this->id), $message);
        })()->then(function ($response) {
            return $this->messages->get('id', $response->id) ?: $this->messages->create($response, true);
        });
    }

    /**
     * Sends an embed to the thread.
     *
     * @deprecated 10.0.0 Use `Channel::sendMessage` with `MessageBuilder::addEmbed()`
     *
     * @see Thread::sendMessage()
     *
     * @param Embed $embed Embed to send.
     *
     * @return ExtendedPromiseInterface<Message>
     *
     * @todo Make it in a trait along with Channel
     */
    public function sendEmbed(Embed $embed): ExtendedPromiseInterface
    {
        return $this->sendMessage(MessageBuilder::new()
            ->addEmbed($embed));
    }

    /**
     * Broadcasts that you are typing to the thread. Lasts for 5 seconds.
     *
     * @link https://discord.com/developers/docs/resources/channel#trigger-typing-indicator
     *
     * @since 10.0.0
     *
     * @throws \RuntimeException
     *
     * @return ExtendedPromiseInterface
     */
    public function broadcastTyping(): ExtendedPromiseInterface
    {
        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_TYPING, $this->id));
    }

    /**
     * Creates a message collector for the channel.
     *
     * @param callable $filter  The filter function. Returns true or false.
     * @param array    $options
     * @param int      $options ['time']  Time in milliseconds until the collector finishes or false.
     * @param int      $options ['limit'] The amount of messages allowed or false.
     *
     * @return ExtendedPromiseInterface<Collection<Message>>
     *
     * @todo Make it in a trait along with Channel
     */
    public function createMessageCollector(callable $filter, array $options = []): ExtendedPromiseInterface
    {
        $deferred = new Deferred();
        $messages = new Collection([], null, null);
        $timer = null;

        $options = array_merge([
            'time' => false,
            'limit' => false,
        ], $options);

        $eventHandler = function (Message $message) use (&$eventHandler, $filter, $options, &$messages, &$deferred, &$timer) {
            // Reject messages not in this thread
            if ($message->channel_id != $this->id) {
                return;
            }

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

    /**
     * Returns the bot's permissions in the thread.
     *
     * @return RolePermission|null
     */
    public function getBotPermissions(): ?RolePermission
    {
        if (! $guild = $this->guild) {
            return null;
        }

        return $guild->members->get('id', $this->discord->id)->getPermissions($this);
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/channel#start-thread-without-message-json-params
     */
    public function getCreatableAttributes(): array
    {
        $attr = [
            'name' => $this->name,
        ];

        if ($this->type == Channel::TYPE_PRIVATE_THREAD) {
            $attr += $this->makeOptionalAttributes([
                'invitable' => $this->invitable,
            ]);
        }

        $attr += $this->makeOptionalAttributes([
            'auto_archive_duration' => $this->auto_archive_duration,
            'type' => $this->type,
            'rate_limit_per_user' => $this->rate_limit_per_user,
        ]);

        return $attr;
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/channel#modify-channel-json-params-thread
     */
    public function getUpdatableAttributes(): array
    {
        $attr = [
            'name' => $this->name,
            'archived' => $this->archived,
            'auto_archive_duration' => $this->auto_archive_duration,
            'locked' => $this->locked,
            'rate_limit_per_user' => $this->rate_limit_per_user,
        ];

        if ($this->type == Channel::TYPE_PRIVATE_THREAD) {
            $attr['invitable'] = $this->invitable;
        }

        $attr += $this->makeOptionalAttributes([
            'flags' => $this->flags,
            'applied_tags' => $this->applied_tags,
        ]);

        return $attr;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_id' => $this->guild_id,
            'parent_id' => $this->parent_id,
            'channel_id' => $this->id,
            'thread_id' => $this->id,
        ];
    }

    /**
     * Returns a formatted mention.
     *
     * @return string A formatted mention.
     */
    public function __toString(): string
    {
        return "<#{$this->id}>";
    }
}
