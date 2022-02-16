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
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\Thread\Member as ThreadMember;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\Channel\MessageRepository;
use Discord\Repository\Thread\MemberRepository;
use Discord\WebSockets\Event;
use React\Promise\ExtendedPromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

/**
 * Represents a Discord thread.
 *
 * @property string            $id                    The ID of the thread.
 * @property string            $guild_id              The ID of the guild which the thread belongs to.
 * @property string            $name                  The name of the thread.
 * @property string            $last_message_id       The ID of the last message sent in the thread.
 * @property Carbon|null       $last_pin_timestamp    The timestamp when the last message was pinned in the thread.
 * @property int               $rate_limit_per_user   Amount of seconds a user has to wait before sending a new message.
 * @property string            $owner_id              The ID of the owner of the thread.
 * @property string            $parent_id             The ID of the channel which the thread was started in.
 * @property int               $message_count         An approximate count of the number of messages sent in the thread. Stops counting at 50.
 * @property int               $member_count          An approximate count of the number of members in the thread. Stops counting at 50.
 * @property Guild|null        $guild                 The guild which the thread belongs to.
 * @property User|null         $owner                 The owner of the thread.
 * @property Member|null       $owner_member          The member object for the owner of the thread.
 * @property Channel|null      $parent                The channel which the thread was created in.
 * @property bool              $archived              Whether the thread has been archived.
 * @property bool              $locked                Whether the thread has been locked.
 * @property int               $auto_archive_duration The number of minutes of inactivity until the thread is automatically archived.
 * @property string|null       $archiver_id           The ID of the user that archived the thread, if any.
 * @property User|null         $archiver              The user that archived the thread, if any.
 * @property Member|null       $archiver_member       The corresponding member object for the user that archived the thread, if any.
 * @property Carbon            $archive_timestamp     The time that the thread's archive status was changed.
 * @property MessageRepository $messages              Repository of messages sent in the thread.
 * @property MemberRepository  $members               Repository of members in the thread.
 *
 * @method ExtendedPromiseInterface sendMessage(MessageBuilder $builder)
 * @method ExtendedPromiseInterface sendMessage(string $text, bool $tts = false, Embed|array $embed = null, array $allowed_mentions = null, ?Message $replyTo = null)
 */
class Thread extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
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
    ];

    /**
     * @inheritdoc
     */
    protected $visible = [
        'guild',
        'owner',
        'owner_member',
        'parent',
        'archived',
        'locked',
        'auto_archive_duration',
        'archiver_id',
        'archiver',
        'archiver_member',
        'archive_timestamp',
    ];

    /**
     * @inheritdoc
     */
    protected $repositories = [
        'messages' => MessageRepository::class,
        'members' => MemberRepository::class,
    ];

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
        if ($this->guild) {
            return $this->guild->members->get('id', $this->owner_id);
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
        if ($this->guild) {
            return $this->guild->channels->get('id', $this->parent_id);
        }

        return $this->discord->getChannel($this->parent_id);
    }

    /**
     * Returns the timestamp when the last message was pinned in the thread.
     *
     * @return Carbon|null
     */
    protected function getLastPinTimestampAttribute(): ?Carbon
    {
        if (isset($this->attributes['last_pin_timestamp'])) {
            return new Carbon($this->attributes['last_pin_timestamp']);
        }

        return null;
    }

    /**
     * Returns whether the thread is archived.
     *
     * @return bool
     */
    protected function getArchivedAttribute(): bool
    {
        return $this->thread_metadata->archived;
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
     * Returns the number of minutes of inactivity required for the thread
     * to auto archive.
     *
     * @return int
     */
    protected function getAutoArchiveDurationAttribute(): int
    {
        return $this->thread_metadata->auto_archive_duration;
    }

    /**
     * Returns the ID of the user who archived the thread.
     *
     * @return string|null
     */
    protected function getArchiverIdAttribute(): ?string
    {
        return $this->thread_metadata->archiver_id ?? null;
    }

    /**
     * Set whether the thread is archived.
     *
     * @param bool $value
     */
    protected function setArchivedAttribute(bool $value)
    {
        $this->thread_metadata->archived = $value;
    }

    /**
     * Set whether the thread is locked.
     *
     * @param bool $value
     */
    protected function setLockedAttribute(bool $value)
    {
        $this->thread_metadata->locked = $value;
    }

    /**
     * Set the number of minutes of inactivity required for the thread to auto archive.
     *
     * @param int $value
     */
    protected function setAutoArchiveDurationAttribute(int $value)
    {
        $this->thread_metadata->auto_archive_duration = $value;
    }

    /**
     * Returns the user who archived the thread.
     *
     * @return User|null
     */
    protected function getArchiverAttribute(): ?User
    {
        if ($this->archiver_id) {
            return $this->discord->users->get('id', $this->archiver_id);
        }

        return null;
    }

    /**
     * Returns the member object for the user who archived the thread.
     *
     * @return Member|null
     */
    protected function getArchiverMemberAttribute(): ?Member
    {
        if ($this->archiver_id && $this->guild) {
            return $this->guild->members->get('id', $this->archiver_id);
        }

        return null;
    }

    /**
     * Returns the time that the thread's archive status was changed.
     *
     * Note that this does not mean the time that the thread was archived - it can
     * also mean the time when the thread was created, archived, unarchived etc.
     *
     * @return Carbon
     */
    protected function getArchiveTimestampAttribute(): Carbon
    {
        return new Carbon($this->thread_metadata->archive_timestamp);
    }

    /**
     * Attempts to join the thread.
     *
     * @see https://discord.com/developers/docs/resources/channel#join-thread
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
     * @see https://discord.com/developers/docs/resources/channel#add-thread-member
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
     * @see https://discord.com/developers/docs/resources/channel#leave-thread
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
     * @see https://discord.com/developers/docs/resources/channel#remove-thread-member
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
     * @return ExtendedPromiseInterface
     */
    public function rename(string $name, ?string $reason = null): ExtendedPromiseInterface
    {
        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::THREAD, $this->id), ['name' => $name], $headers);
    }

    /**
     * Archive the thread.
     *
     * @param string|null $reason Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface
     */
    public function archive(?string $reason = null): ExtendedPromiseInterface
    {
        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::THREAD, $this->id), ['archived' => true], $headers);
    }

    /**
     * Unarchive the thread.
     *
     * @param string|null $reason Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface
     */
    public function unarchive(?string $reason = null): ExtendedPromiseInterface
    {
        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::THREAD, $this->id), ['archived' => false], $headers);
    }

    /**
     * Set auto archive duration of the thread.
     *
     * @param int         $duration Duration in minutes.
     * @param string|null $reason   Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface
     */
    public function setAutoArchiveDuration(int $duration, ?string $reason = null): ExtendedPromiseInterface
    {
        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::THREAD, $this->id), ['auto_archive_duration' => $duration], $headers);
    }

    /**
     * Returns the thread's pinned messages.
     *
     * @see https://discord.com/developers/docs/resources/channel#get-pinned-messages
     *
     * @return ExtendedPromiseInterface<Collection<Message>>
     */
    public function getPinnedMessages(): ExtendedPromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::CHANNEL_PINS, $this->id))
            ->then(function ($responses) {
                $messages = Collection::for(Message::class);

                foreach ($responses as $response) {
                    if (! $message = $this->messages->get('id', $response->id)) {
                        $message = $this->factory->create(Message::class, $response, true);
                    }

                    $messages->push($message);
                }

                return $messages;
            });
    }

    /**
     * Bulk deletes an array of messages.
     *
     * @see https://discord.com/developers/docs/resources/channel#bulk-delete-messages
     *
     * @param array|Traversable $messages
     * @param string|null       $reason   Reason for Audit Log (only for bulk messages).
     *
     * @return ExtendedPromiseInterface
     */
    public function deleteMessages($messages, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! is_array($messages) && ! ($messages instanceof Traversable)) {
            return \React\Promise\reject(new \Exception('$messages must be an array or implement Traversable.'));
        }

        $count = count($messages);

        if ($count == 0) {
            return \React\Promise\resolve();
        } elseif ($count == 1) {
            foreach ($messages as $message) {
                if ($message instanceof Message) {
                    $message = $message->id;
                }

                return $this->http->delete(Endpoint::bind(Endpoint::CHANNEL_MESSAGE, $this->id, $message));
            }
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        $promises = [];
        $chunks = array_chunk(array_map(function ($message) {
            if ($message instanceof Message) {
                return $message->id;
            }

            return $message;
        }, $messages), 100);

        foreach ($chunks as $messages) {
            $promises[] = $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGES_BULK_DELETE, $this->id), [
                'messages' => $messages,
            ], $headers);
        }

        return \React\Promise\all($promises);
    }

    /**
     * Fetches the message history of the thread with a given array
     * of arguments.
     *
     * @see https://discord.com/developers/docs/resources/channel#get-channel-messages
     *
     * @param array $options
     *
     * @return ExtendedPromiseInterface<Collection<Message>>
     */
    public function getMessageHistory(array $options): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults(['limit' => 100, 'cache' => false])
            ->setDefined(['before', 'after', 'around'])
            ->setAllowedTypes('before', [Message::class, 'string'])
            ->setAllowedTypes('after', [Message::class, 'string'])
            ->setAllowedTypes('around', [Message::class, 'string'])
            ->setAllowedValues('limit', range(1, 100));

        $options = $resolver->resolve($options);

        if (isset($options['before'], $options['after']) ||
            isset($options['before'], $options['around']) ||
            isset($options['around'], $options['after'])) {
            return \React\Promise\reject(new \Exception('Can only specify one of before, after and around.'));
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
            $messages = new Collection();

            foreach ($responses as $response) {
                if (! $message = $this->messages->get('id', $response->id)) {
                    $message = $this->factory->create(Message::class, $response, true);
                    $this->messages->push($message);
                }

                $messages->push($message);
            }

            return $messages;
        });
    }

    /**
     * Pins a message in the thread.
     *
     * @see https://discord.com/developers/docs/resources/channel#pin-message
     *
     * @param Message     $message
     * @param string|null $reason  Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function pinMessage(Message $message, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($message->pinned) {
            return \React\Promise\reject(new \Exception('This message is already pinned.'));
        }

        if ($message->channel_id != $this->id) {
            return \React\Promise\reject(new \Exception('You cannot pin a message not sent in this thread.'));
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
     * @see https://discord.com/developers/docs/resources/channel#unpin-message
     *
     * @param Message     $message
     * @param string|null $reason  Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function unpinMessage(Message $message, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! $message->pinned) {
            return \React\Promise\reject(new \Exception('This message is not pinned.'));
        }

        if ($message->channel_id != $this->id) {
            return \React\Promise\reject(new \Exception('You cannot un-pin a message not sent in this thread.'));
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
     * Takes a `MessageBuilder` or content of the message for the first parameter. If the first parameter
     * is an instance of `MessageBuilder`, the rest of the arguments are disregarded.
     *
     * @see https://discord.com/developers/docs/resources/channel#create-message
     *
     * @param MessageBuilder|string $message          The message builder that should be converted into a message, or the string content of the message.
     * @param bool                  $tts              Whether the message is TTS.
     * @param Embed|array|null      $embed            An embed object or array to send in the message.
     * @param array|null            $allowed_mentions Allowed mentions object for the message.
     * @param Message|null          $replyTo          Sends the message as a reply to the given message instance.
     *
     * @return ExtendedPromiseInterface<Message>
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
            return $this->factory->create(Message::class, $response, true);
        });
    }

    /**
     * Sends an embed to the thread.
     *
     * @see Thread::sendMessage()
     *
     * @param Embed $embed Embed to send.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function sendEmbed(Embed $embed): ExtendedPromiseInterface
    {
        return $this->sendMessage(MessageBuilder::new()
            ->addEmbed($embed));
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
                $messages->push($message);

                if ($options['limit'] !== false && sizeof($messages) >= $options['limit']) {
                    $this->discord->removeListener(Event::MESSAGE_CREATE, $eventHandler);
                    $deferred->resolve($messages);

                    if (! is_null($timer)) {
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
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'rate_limit_per_user' => $this->rate_limit_per_user,
            'archived' => $this->archived,
            'auto_archive_duration' => $this->auto_archive_duration,
            'locked' => $this->locked,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'thread_id' => $this->id,
            'channel_id' => $this->id,
            'parent_id' => $this->parent_id,
        ];
    }

    /**
     * Returns a formatted mention.
     *
     * @return string A formatted mention.
     */
    public function __toString()
    {
        return "<#{$this->id}>";
    }
}
