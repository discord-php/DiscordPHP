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
use Discord\Helpers\CollectionInterface;
use Discord\Parts\Channel\Webhook;
use Discord\Parts\Guild\AutoModeration\Rule;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Integration;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Part;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\User;
use ReflectionClass;

/**
 * Represents an audit log query from a guild.
 *
 * @link https://discord.com/developers/docs/resources/audit-log#audit-log-object
 *
 * @since 5.1.0
 *
 * @property CollectionInterface|Command[]        $application_commands   List of application commands referenced in the audit log.
 * @property CollectionInterface|Entry[]          $audit_log_entries      List of audit log entries.
 * @property CollectionInterface|Rule[]           $auto_moderation_rules  List of auto moderation rules referenced in the audit log.
 * @property CollectionInterface|ScheduledEvent[] $guild_scheduled_events List of guild scheduled events referenced in the audit log.
 * @property CollectionInterface|Integration[]    $integrations           List of partial integration objects.
 * @property CollectionInterface|Thread[]         $threads                List of threads referenced in the audit log.
 * @property CollectionInterface|User[]           $users                  List of users referenced in the audit log.
 * @property CollectionInterface|Webhook[]        $webhooks               List of webhooks referenced in the audit log.
 *
 * @property      string     $guild_id
 * @property-read Guild|null $guild
 */
class AuditLog extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'application_commands',
        'webhooks',
        'auto_moderation_rules',
        'guild_scheduled_events',
        'users',
        'audit_log_entries',
        'integrations',
        'threads',

        // @internal
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
     * Returns a collection of application commands found in the audit log.
     *
     * @return CollectionInterface|Command[]
     */
    protected function getApplicationCommandsAttribute(): CollectionInterface
    {
        $collection = Collection::for(Command::class);

        foreach ($this->attributes['application_commands'] ?? [] as $application_commands) {
            $collection->pushItem($this->factory->part(Command::class, (array) $application_commands, true));
        }

        return $collection;
    }

    /**
     * Returns a collection of audit log entries.
     *
     * @return CollectionInterface|Entry[]
     */
    protected function getAuditLogEntriesAttribute(): CollectionInterface
    {
        $collection = Collection::for(Entry::class);

        foreach ($this->attributes['audit_log_entries'] ?? [] as $entry) {
            $collection->pushItem($this->createOf(Entry::class, $entry));
        }

        return $collection;
    }

    /**
     * Returns a collection of auto moderation rules found in the audit log.
     *
     * @return CollectionInterface|Rule[]
     */
    protected function getAutoModerationRulesAttribute(): CollectionInterface
    {
        $collection = Collection::for(Rule::class);

        foreach ($this->attributes['auto_moderation_rules'] ?? [] as $rule) {
            $collection->pushItem($this->factory->part(Rule::class, (array) $rule, true));
        }

        return $collection;
    }

    /**
     * Returns a collection of guild scheduled events found in the audit log.
     *
     * @return CollectionInterface|ScheduledEvent[]
     */
    protected function getGuildScheduledEventsAttribute(): CollectionInterface
    {
        $collection = Collection::for(ScheduledEvent::class);

        foreach ($this->attributes['guild_scheduled_events'] ?? [] as $scheduled_event) {
            $collection->pushItem($this->factory->part(ScheduledEvent::class, (array) $scheduled_event, true));
        }

        return $collection;
    }

    /**
     * Returns a collection of partial integrations found in the audit log.
     *
     * @link https://discord.com/developers/docs/resources/audit-log#audit-log-object-example-partial-integration-object
     *
     * @return CollectionInterface|Integration[]
     */
    protected function getIntegrationsAttribute(): CollectionInterface
    {
        $collection = Collection::for(Integration::class);

        foreach ($this->attributes['integrations'] ?? [] as $integration) {
            $collection->pushItem($this->factory->part(Integration::class, (array) $integration, true));
        }

        return $collection;
    }

    /**
     * Returns a collection of threads found in the audit log.
     *
     * @return CollectionInterface|Thread[]
     */
    protected function getThreadsAttribute(): CollectionInterface
    {
        $collection = Collection::for(Thread::class);

        foreach ($this->attributes['threads'] ?? [] as $thread) {
            $collection->pushItem($this->factory->part(Thread::class, (array) $thread, true));
        }

        return $collection;
    }

    /**
     * Returns a collection of users found in the audit log.
     *
     * @return CollectionInterface|User[]
     */
    protected function getUsersAttribute(): CollectionInterface
    {
        $collection = Collection::for(User::class);

        foreach ($this->attributes['users'] ?? [] as $user) {
            $collection->pushItem($this->discord->users->get('id', $user->id) ?: $this->factory->part(User::class, (array) $user, true));
        }

        return $collection;
    }

    /**
     * Returns a collection of webhooks found in the audit log.
     *
     * @return CollectionInterface|Webhook[]
     */
    protected function getWebhooksAttribute(): CollectionInterface
    {
        $collection = Collection::for(Webhook::class);

        foreach ($this->attributes['webhooks'] ?? [] as $webhook) {
            $collection->pushItem($this->factory->part(Webhook::class, (array) $webhook, true));
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
     * @return CollectionInterface|Entry[]
     */
    public function searchByType(int $action_type): CollectionInterface
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
