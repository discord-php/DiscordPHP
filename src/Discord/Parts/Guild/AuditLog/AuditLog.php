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

namespace Discord\Parts\Guild\AuditLog;

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
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
 * @property ExCollectionInterface<Command>|Command[]               $application_commands   List of application commands referenced in the audit log.
 * @property ExCollectionInterface<Entry>|Entry[]                   $audit_log_entries      List of audit log entries.
 * @property ExCollectionInterface<Rule>|Rule[]                     $auto_moderation_rules  List of auto moderation rules referenced in the audit log.
 * @property ExCollectionInterface<ScheduledEvent>|ScheduledEvent[] $guild_scheduled_events List of guild scheduled events referenced in the audit log.
 * @property ExCollectionInterface<Integration>|Integration[]       $integrations           List of partial integration objects.
 * @property ExCollectionInterface<Thread>|Thread[]                 $threads                List of threads referenced in the audit log.
 * @property ExCollectionInterface<User>|User[]                     $users                  List of users referenced in the audit log.
 * @property ExCollectionInterface<Webhook>|Webhook[]               $webhooks               List of webhooks referenced in the audit log.
 *
 * @property      string     $guild_id
 * @property-read Guild|null $guild
 */
class AuditLog extends Part
{
    /**
     * @inheritDoc
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
     * @return ExCollectionInterface<Command>|Command[]
     */
    protected function getApplicationCommandsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('application_commands', Command::class);
    }

    /**
     * Returns a collection of audit log entries.
     *
     * @return ExCollectionInterface<Entry>|Entry[]
     */
    protected function getAuditLogEntriesAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('audit_log_entries', Entry::class);
    }

    /**
     * Returns a collection of auto moderation rules found in the audit log.
     *
     * @return ExCollectionInterface<Rule>|Rule[]
     */
    protected function getAutoModerationRulesAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('auto_moderation_rules', Rule::class);
    }

    /**
     * Returns a collection of guild scheduled events found in the audit log.
     *
     * @return ExCollectionInterface<ScheduledEvent>|ScheduledEvent[]
     */
    protected function getGuildScheduledEventsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('guild_scheduled_events', ScheduledEvent::class);
    }

    /**
     * Returns a collection of partial integrations found in the audit log.
     *
     * @link https://discord.com/developers/docs/resources/audit-log#audit-log-object-example-partial-integration-object
     *
     * @return ExCollectionInterface<Integration>|Integration[]
     */
    protected function getIntegrationsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('integrations', Integration::class);
    }

    /**
     * Returns a collection of threads found in the audit log.
     *
     * @return ExCollectionInterface<Thread>|Thread[]
     */
    protected function getThreadsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('threads', Thread::class);
    }

    /**
     * Returns a collection of users found in the audit log.
     *
     * @return ExCollectionInterface<User>|User[]
     */
    protected function getUsersAttribute(): ExCollectionInterface
    {
        if (isset($this->attributes['users']) && $this->attributes['users'] instanceof ExCollectionInterface) {
            return $this->attributes['users'];
        }

        $collection = Collection::for(User::class);

        foreach ($this->attributes['users'] ?? [] as $user) {
            $collection->pushItem($this->discord->users->get('id', $user->id) ?? $this->factory->part(User::class, (array) $user, true));
        }

        $this->attributes['users'] = $collection;

        return $collection;
    }

    /**
     * Returns a collection of webhooks found in the audit log.
     *
     * @return ExCollectionInterface<Webhook>|Webhook[]
     */
    protected function getWebhooksAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('webhooks', Webhook::class);
    }

    /**
     * Searches the audit log entries with action type.
     *
     * @param int $action_type
     *
     * @throws \InvalidArgumentException
     *
     * @return ExCollectionInterface<Entry>|Entry[]
     */
    public function searchByType(int $action_type): ExCollectionInterface
    {
        $types = array_values((new ReflectionClass(Entry::class))->getConstants());

        if (! in_array($action_type, $types)) {
            throw new \InvalidArgumentException("The given action type `{$action_type}` is not valid.");
        }

        return $this->audit_log_entries->filter(function (Entry $entry) use ($action_type) {
            return $entry->action_type === $action_type;
        });
    }
}
