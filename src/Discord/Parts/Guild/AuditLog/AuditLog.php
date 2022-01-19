<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild\AuditLog;

use Discord\Helpers\Collection;
use Discord\Parts\Channel\Webhook;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Parts\Part;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\User;
use ReflectionClass;

/**
 * Represents an audit log query from a guild.
 *
 * @see https://discord.com/developers/docs/resources/audit-log#audit-log-object
 *
 * @property Collection|Entry[]               $audit_log_entries      List of audit log entries.
 * @property Collection|GuildScheduledEvent[] $guild_scheduled_events List of guild scheduled events found in the audit log.
 * @property Collection                       $integrations           List of partial integration objects.
 * @property Collection|Threads[]             $threads                List of threads found in the audit log.
 * @property Collection|User[]                $users                  List of users found in the audit log.
 * @property Collection|Webhook[]             $webhooks               List of webhooks found in the audit log.
 * @property string                           $guild_id
 * @property Guild                            $guild
 */
class AuditLog extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'webhooks',
        'guild_scheduled_events',
        'users',
        'audit_log_entries',
        'integrations',
        'threads',
        'guild_id',
    ];

    /**
     * Returns the guild the audit log belongs to.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns a collection of webhooks found in the audit log.
     *
     * @return Collection|Webhook[]
     */
    protected function getWebhooksAttribute(): Collection
    {
        $collection = Collection::for(Webhook::class);

        foreach ($this->attributes['webhooks'] ?? [] as $webhook) {
            $collection->push($this->factory->create(Webhook::class, $webhook, true));
        }

        return $collection;
    }

    /**
     * Returns a collection of guild scheduled events found in the audit log.
     *
     * @return Collection|ScheduledEvent[]
     */
    protected function getGuildScheduledEventsAttribute(): Collection
    {
        $collection = Collection::for(ScheduledEvent::class);

        foreach ($this->attributes['guild_scheduled_events'] ?? [] as $scheduled_event) {
            $collection->push($this->factory->create(ScheduledEvent::class, $scheduled_event, true));
        }

        return $collection;
    }

    /**
     * Returns a collection of users found in the audit log.
     *
     * @return Collection|User[]
     */
    protected function getUsersAttribute(): Collection
    {
        $collection = Collection::for(User::class);

        foreach ($this->attributes['users'] ?? [] as $user) {
            if ($user = $this->discord->users->get('id', $user->id)) {
                $collection->push($user);
            } else {
                $collection->push($this->factory->create(User::class, $user, true));
            }
        }

        return $collection;
    }

    /**
     * Returns a collection of audit log entries.
     *
     * @return Collection|Entry[]
     */
    protected function getAuditLogEntriesAttribute(): Collection
    {
        $collection = Collection::for(Entry::class);

        foreach ($this->attributes['audit_log_entries'] ?? [] as $entry) {
            $collection->push($this->factory->create(Entry::class, $entry, true));
        }

        return $collection;
    }

    /**
     * Returns a collection of integrations found in the audit log.
     *
     * @return Collection
     */
    protected function getIntegrationsAttribute(): Collection
    {
        return new Collection($this->attributes['integrations'] ?? []);
    }

    /**
     * Returns a collection of threads found in the audit log.
     *
     * @return Collection|Thread[]
     */
    protected function getThreadsAttribute(): Collection
    {
        $collection = Collection::for(Thread::class);

        foreach ($this->attributes['threads'] ?? [] as $thread) {
            $collection->push($this->factory->create(Thread::class, $thread, true));
        }

        return $collection;
    }

    /**
     * Searches the audit log entries with action type.
     *
     * @param int $action_type
     *
     * @throws \InvalidArgumentException
     *
     * @return Collection|Entry[]
     */
    public function searchByType(int $action_type): Collection
    {
        $types = array_values((new ReflectionClass(Entry::class))->getConstants());

        if (! in_array($action_type, $types)) {
            throw new \InvalidArgumentException("The given action type `{$action_type}` is not valid.");
        }

        return $this->audit_log_entries->filter(function (Entry $entry) use ($action_type) {
            return $entry->action_type == $action_type;
        });
    }
}
