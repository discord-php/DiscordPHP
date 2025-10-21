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

namespace Discord\Parts\Thread;

use Carbon\Carbon;
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\ChannelTrait;
use Discord\Parts\Channel\ThreadMetadata;
use Discord\Parts\Part;
use Discord\Parts\Thread\Member as ThreadMember;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\Channel\MessageRepository;
use Discord\Repository\Thread\MemberRepository;
use React\Promise\PromiseInterface;
use Stringable;

use function React\Promise\reject;

/**
 * Represents a Discord thread.
 *
 * @link https://discord.com/developers/docs/topics/threads
 *
 * @since 7.0.0
 *
 * @property int            $message_count      Number of messages (not including the initial message or deleted messages) in a thread (if the thread was created before July 1, 2022, the message count is inaccurate when it's greater than 50).
 * @property int            $member_count       An approximate count of the number of members in the thread. Stops counting at 50.
 * @property ThreadMetadata $thread_metadata    Thread-specific fields not needed by other channels.
 * @property int|null       $flags              Channel flags combined as a bitfield. PINNED can only be set for threads in forum channels.
 * @property int|null       $total_message_sent Number of messages ever sent in a thread, it's similar to `message_count` on message creation, but will not decrement the number when a message is deleted.
 * @property string[]|null  $applied_tags       The IDs of the set of tags that have been applied to a thread in a forum channel, limited to 5.
 *
 * @property-read bool         $archived              Whether the thread has been archived.
 * @property-read int|null     $auto_archive_duration The number of minutes of inactivity until the thread is automatically archived.
 * @property-read Carbon       $archive_timestamp     The time that the thread's archive status was changed.
 * @property-read bool         $locked                Whether the thread has been locked.
 * @property-read bool|null    $invitable             Whether non-moderators can add other non-moderators to a thread; only available on private threads.
 * @property-read ?Carbon|null $create_timestamp      Timestamp when the thread was created; only populated for threads created after 2022-01-09.
 */
class Thread extends Part implements Stringable
{
    use ChannelTrait;

    /** This thread is pinned to the top of its parent GUILD_FORUM or GUILD_MEDIA channel. */
    public const FLAG_PINNED = (1 << 1);

    /**
     * @inheritDoc
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

        // events
        'newly_created',
    ];

    /**
     * @inheritDoc
     */
    protected $hidden = [
        'member',
    ];

    /**
     * @inheritDoc
     */
    protected $repositories = [
        'members' => MemberRepository::class,
        'messages' => MessageRepository::class,
    ];

    /**
     * @inheritDoc
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
     * Returns the thread metadata.
     *
     * @return ThreadMetadata|null
     *
     * @since 10.22.0
     */
    protected function getThreadMetadataAttribute(): ?ThreadMetadata
    {
        return $this->attributePartHelper('thread_metadata', ThreadMetadata::class);
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
    protected function setArchivedAttribute(bool $value): void
    {
        $this->attributes['thread_metadata']->archived = $value;
    }

    /**
     * Set whether the thread is locked.
     *
     * @param bool $value
     */
    protected function setLockedAttribute(bool $value): void
    {
        $this->attributes['thread_metadata']->locked = $value;
    }

    /**
     * Sets whether members without `MANAGE_THREADS` can invite other members without `MANAGE_THREADS`
     * Always `null` in public threads.
     *
     * @param bool $value
     */
    protected function setInvitableAttribute(bool $value): void
    {
        if ($this->type === Channel::TYPE_PUBLIC_THREAD) {
            return;
        }
        $this->attributes['thread_metadata']->invitable = $value;
    }

    /**
     * Set the number of minutes of inactivity required for the thread to auto
     * archive.
     *
     * @param int $value
     */
    protected function setAutoArchiveDurationAttribute(int $value): void
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
        return Carbon::parse($this->thread_metadata->archive_timestamp);
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
        return Carbon::parse($this->thread_metadata->create_timestamp);
    }

    /**
     * Attempts to join the thread.
     *
     * @link https://discord.com/developers/docs/resources/channel#join-thread
     *
     * @return PromiseInterface
     */
    public function join(): PromiseInterface
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
     * @return PromiseInterface
     */
    public function addMember($user): PromiseInterface
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
     * @return PromiseInterface
     */
    public function leave(): PromiseInterface
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
     * @return PromiseInterface
     */
    public function removeMember($user): PromiseInterface
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
     * @return PromiseInterface<self>
     */
    public function rename(string $name, ?string $reason = null): PromiseInterface
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
     * @return PromiseInterface<self>
     */
    public function archive(?string $reason = null): PromiseInterface
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
     * @return PromiseInterface<self>
     */
    public function unarchive(?string $reason = null): PromiseInterface
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
     * @return PromiseInterface<self>
     */
    public function setAutoArchiveDuration(int $duration, ?string $reason = null): PromiseInterface
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
     * @inheritDoc
     *
     * @link https://discord.com/developers/docs/resources/channel#start-thread-without-message-json-params
     */
    public function getCreatableAttributes(): array
    {
        $attr = [
            'name' => $this->name,
        ];

        if ($this->type === Channel::TYPE_PRIVATE_THREAD) {
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
     * @inheritDoc
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

        if ($this->type === Channel::TYPE_PRIVATE_THREAD) {
            $attr['invitable'] = $this->invitable;
        }

        $attr += $this->makeOptionalAttributes([
            'flags' => $this->flags,
            'applied_tags' => $this->applied_tags,
        ]);

        return $attr;
    }

    /**
     * @inheritDoc
     */
    public function save(): PromiseInterface
    {
        if ($botperms = $this->getBotPermissions()) {
            if (! $botperms->manage_threads) {
                return reject(new NoPermissionsException('The bot is missing the MANAGE_THREADS permission to save this thread.'));
            }
        }

        if (! isset($this->attributes['id']) || ! $this->created) {
            return reject(new \RuntimeException('Please use Channel::startThread() instead.'));
        }

        if ($channel = $this->discord->getChannel($this->parent_id)) {
            return $channel->threads->save($this);
        }

        return parent::save();
    }

    /**
     * @inheritDoc
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
