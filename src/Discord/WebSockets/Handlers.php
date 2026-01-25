<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets;

use Discord\WebSockets\Events\ApplicationCommandPermissionsUpdate;
use Discord\WebSockets\Events\AutoModerationActionExecution;
use Discord\WebSockets\Events\AutoModerationRuleCreate;
use Discord\WebSockets\Events\AutoModerationRuleDelete;
use Discord\WebSockets\Events\AutoModerationRuleUpdate;
use Discord\WebSockets\Events\ChannelCreate;
use Discord\WebSockets\Events\ChannelDelete;
use Discord\WebSockets\Events\ChannelPinsUpdate;
use Discord\WebSockets\Events\ChannelUpdate;
use Discord\WebSockets\Events\EntitlementCreate;
use Discord\WebSockets\Events\EntitlementDelete;
use Discord\WebSockets\Events\EntitlementUpdate;
use Discord\WebSockets\Events\GuildAuditLogEntryCreate;
use Discord\WebSockets\Events\GuildBanAdd;
use Discord\WebSockets\Events\GuildBanRemove;
use Discord\WebSockets\Events\GuildCreate;
use Discord\WebSockets\Events\GuildDelete;
use Discord\WebSockets\Events\GuildEmojisUpdate;
use Discord\WebSockets\Events\GuildIntegrationsUpdate;
use Discord\WebSockets\Events\GuildMemberAdd;
use Discord\WebSockets\Events\GuildMemberRemove;
use Discord\WebSockets\Events\GuildMemberUpdate;
use Discord\WebSockets\Events\GuildRoleCreate;
use Discord\WebSockets\Events\GuildRoleDelete;
use Discord\WebSockets\Events\GuildRoleUpdate;
use Discord\WebSockets\Events\GuildScheduledEventCreate;
use Discord\WebSockets\Events\GuildScheduledEventDelete;
use Discord\WebSockets\Events\GuildScheduledEventUpdate;
use Discord\WebSockets\Events\GuildScheduledEventUserAdd;
use Discord\WebSockets\Events\GuildScheduledEventUserRemove;
use Discord\WebSockets\Events\GuildSoundboardSoundCreate;
use Discord\WebSockets\Events\GuildSoundboardSoundDelete;
use Discord\WebSockets\Events\GuildSoundboardSoundUpdate;
use Discord\WebSockets\Events\GuildStickersUpdate;
use Discord\WebSockets\Events\GuildUpdate;
use Discord\WebSockets\Events\IntegrationCreate;
use Discord\WebSockets\Events\IntegrationDelete;
use Discord\WebSockets\Events\IntegrationUpdate;
use Discord\WebSockets\Events\InteractionCreate;
use Discord\WebSockets\Events\InviteCreate;
use Discord\WebSockets\Events\InviteDelete;
use Discord\WebSockets\Events\MessageCreate;
use Discord\WebSockets\Events\MessageDelete;
use Discord\WebSockets\Events\MessageDeleteBulk;
use Discord\WebSockets\Events\MessagePollVoteAdd;
use Discord\WebSockets\Events\MessagePollVoteRemove;
use Discord\WebSockets\Events\MessageReactionAdd;
use Discord\WebSockets\Events\MessageReactionRemove;
use Discord\WebSockets\Events\MessageReactionRemoveAll;
use Discord\WebSockets\Events\MessageReactionRemoveEmoji;
use Discord\WebSockets\Events\MessageUpdate;
use Discord\WebSockets\Events\PresenceUpdate;
use Discord\WebSockets\Events\SoundboardSounds;
use Discord\WebSockets\Events\StageInstanceCreate;
use Discord\WebSockets\Events\StageInstanceDelete;
use Discord\WebSockets\Events\StageInstanceUpdate;
use Discord\WebSockets\Events\ThreadCreate;
use Discord\WebSockets\Events\ThreadDelete;
use Discord\WebSockets\Events\ThreadListSync;
use Discord\WebSockets\Events\ThreadMembersUpdate;
use Discord\WebSockets\Events\ThreadMemberUpdate;
use Discord\WebSockets\Events\ThreadUpdate;
use Discord\WebSockets\Events\TypingStart;
use Discord\WebSockets\Events\UserUpdate;
use Discord\WebSockets\Events\VoiceServerUpdate;
use Discord\WebSockets\Events\VoiceStateUpdate;
use Discord\WebSockets\Events\WebhooksUpdate;

/**
 * This class contains all the handlers for the individual WebSocket events.
 *
 * @since 2.1.3
 */
class Handlers
{
    /**
     * An array of handlers.
     *
     * @var array Array of handlers.
     */
    protected $handlers = [];

    /**
     * Constructs the list of handlers.
     */
    public function __construct()
    {
        // General
        $this->addHandler(Event::PRESENCE_UPDATE, PresenceUpdate::class);
        $this->addHandler(Event::TYPING_START, TypingStart::class);
        $this->addHandler(Event::VOICE_STATE_UPDATE, VoiceStateUpdate::class);
        $this->addHandler(Event::VOICE_SERVER_UPDATE, VoiceServerUpdate::class);
        $this->addHandler(Event::INTERACTION_CREATE, InteractionCreate::class);
        $this->addHandler(Event::USER_UPDATE, UserUpdate::class);

        // Guild Event handlers
        $this->addHandler(Event::GUILD_CREATE, GuildCreate::class);
        $this->addHandler(Event::GUILD_DELETE, GuildDelete::class);
        $this->addHandler(Event::GUILD_UPDATE, GuildUpdate::class);
        $this->addHandler(Event::GUILD_INTEGRATIONS_UPDATE, GuildIntegrationsUpdate::class);
        $this->addHandler(Event::INTEGRATION_CREATE, IntegrationCreate::class);
        $this->addHandler(Event::INTEGRATION_UPDATE, IntegrationUpdate::class);
        $this->addHandler(Event::INTEGRATION_DELETE, IntegrationDelete::class);
        $this->addHandler(Event::WEBHOOKS_UPDATE, WebhooksUpdate::class);
        $this->addHandler(Event::APPLICATION_COMMAND_PERMISSIONS_UPDATE, ApplicationCommandPermissionsUpdate::class);
        $this->addHandler(Event::GUILD_AUDIT_LOG_ENTRY_CREATE, GuildAuditLogEntryCreate::class);

        // Invite handlers
        $this->addHandler(Event::INVITE_CREATE, InviteCreate::class);
        $this->addHandler(Event::INVITE_DELETE, InviteDelete::class);

        // Channel Event handlers
        $this->addHandler(Event::CHANNEL_CREATE, ChannelCreate::class);
        $this->addHandler(Event::CHANNEL_UPDATE, ChannelUpdate::class);
        $this->addHandler(Event::CHANNEL_DELETE, ChannelDelete::class);
        $this->addHandler(Event::CHANNEL_PINS_UPDATE, ChannelPinsUpdate::class);

        // Ban Event handlers
        $this->addHandler(Event::GUILD_BAN_ADD, GuildBanAdd::class);
        $this->addHandler(Event::GUILD_BAN_REMOVE, GuildBanRemove::class);

        // Guild Emoji Event handler
        $this->addHandler(Event::GUILD_EMOJIS_UPDATE, GuildEmojisUpdate::class);

        // Guild Sticker Event handler
        $this->addHandler(Event::GUILD_STICKERS_UPDATE, GuildStickersUpdate::class);

        // Message handlers
        $this->addHandler(Event::MESSAGE_CREATE, MessageCreate::class, ['message']);
        $this->addHandler(Event::MESSAGE_DELETE, MessageDelete::class);
        $this->addHandler(Event::MESSAGE_DELETE_BULK, MessageDeleteBulk::class);
        $this->addHandler(Event::MESSAGE_UPDATE, MessageUpdate::class);
        $this->addHandler(Event::MESSAGE_REACTION_ADD, MessageReactionAdd::class);
        $this->addHandler(Event::MESSAGE_REACTION_REMOVE, MessageReactionRemove::class);
        $this->addHandler(Event::MESSAGE_REACTION_REMOVE_ALL, MessageReactionRemoveAll::class);
        $this->addHandler(Event::MESSAGE_REACTION_REMOVE_EMOJI, MessageReactionRemoveEmoji::class);
        $this->addHandler(Event::MESSAGE_POLL_VOTE_ADD, MessagePollVoteAdd::class);
        $this->addHandler(Event::MESSAGE_POLL_VOTE_REMOVE, MessagePollVoteRemove::class);

        // New Member Event handlers
        $this->addHandler(Event::GUILD_MEMBER_ADD, GuildMemberAdd::class);
        $this->addHandler(Event::GUILD_MEMBER_REMOVE, GuildMemberRemove::class);
        $this->addHandler(Event::GUILD_MEMBER_UPDATE, GuildMemberUpdate::class);

        // New Role Event handlers
        $this->addHandler(Event::GUILD_ROLE_CREATE, GuildRoleCreate::class);
        $this->addHandler(Event::GUILD_ROLE_DELETE, GuildRoleDelete::class);
        $this->addHandler(Event::GUILD_ROLE_UPDATE, GuildRoleUpdate::class);

        // Guild Scheduled Events Event handlers
        $this->addHandler(Event::GUILD_SCHEDULED_EVENT_CREATE, GuildScheduledEventCreate::class);
        $this->addHandler(Event::GUILD_SCHEDULED_EVENT_UPDATE, GuildScheduledEventUpdate::class);
        $this->addHandler(Event::GUILD_SCHEDULED_EVENT_DELETE, GuildScheduledEventDelete::class);
        $this->addHandler(Event::GUILD_SCHEDULED_EVENT_USER_ADD, GuildScheduledEventUserAdd::class);
        $this->addHandler(Event::GUILD_SCHEDULED_EVENT_USER_REMOVE, GuildScheduledEventUserRemove::class);

        // Thread events
        $this->addHandler(Event::THREAD_CREATE, ThreadCreate::class);
        $this->addHandler(Event::THREAD_UPDATE, ThreadUpdate::class);
        $this->addHandler(Event::THREAD_DELETE, ThreadDelete::class);
        $this->addHandler(Event::THREAD_LIST_SYNC, ThreadListSync::class);
        $this->addHandler(Event::THREAD_MEMBER_UPDATE, ThreadMemberUpdate::class);
        $this->addHandler(Event::THREAD_MEMBERS_UPDATE, ThreadMembersUpdate::class);

        // Stage Instance Event Handlers
        $this->addHandler(Event::STAGE_INSTANCE_CREATE, StageInstanceCreate::class);
        $this->addHandler(Event::STAGE_INSTANCE_UPDATE, StageInstanceUpdate::class);
        $this->addHandler(Event::STAGE_INSTANCE_DELETE, StageInstanceDelete::class);

        // Auto Moderation Event Handlers
        $this->addHandler(Event::AUTO_MODERATION_RULE_CREATE, AutoModerationRuleCreate::class);
        $this->addHandler(Event::AUTO_MODERATION_RULE_UPDATE, AutoModerationRuleUpdate::class);
        $this->addHandler(Event::AUTO_MODERATION_RULE_DELETE, AutoModerationRuleDelete::class);
        $this->addHandler(Event::AUTO_MODERATION_ACTION_EXECUTION, AutoModerationActionExecution::class);

        // Soundboard Event Handlers
        $this->addHandler(Event::GUILD_SOUNDBOARD_SOUND_CREATE, GuildSoundboardSoundCreate::class);
        $this->addHandler(Event::GUILD_SOUNDBOARD_SOUND_UPDATE, GuildSoundboardSoundUpdate::class);
        $this->addHandler(Event::GUILD_SOUNDBOARD_SOUND_DELETE, GuildSoundboardSoundDelete::class);
        $this->addHandler(Event::SOUNDBOARD_SOUNDS, SoundboardSounds::class);

        // Entitlements Event Handlers
        $this->addHandler(Event::ENTITLEMENT_CREATE, EntitlementCreate::class);
        $this->addHandler(Event::ENTITLEMENT_UPDATE, EntitlementUpdate::class);
        $this->addHandler(Event::ENTITLEMENT_DELETE, EntitlementDelete::class);
    }

    /**
     * Adds a handler to the list.
     *
     * @param string $event        The WebSocket event name.
     * @param string $classname    The Event class name.
     * @param array  $alternatives Alternative event names for the handler.
     */
    public function addHandler(string $event, string $classname, array $alternatives = []): void
    {
        $this->handlers[$event] = [
            'class' => $classname,
            'alternatives' => $alternatives,
        ];
    }

    /**
     * Returns a handler.
     *
     * @param string $event The WebSocket event name.
     *
     * @return array|null The Event class name or null;
     */
    public function getHandler(string $event): ?array
    {
        if (isset($this->handlers[$event])) {
            return $this->handlers[$event];
        }

        return null;
    }

    /**
     * Returns the handlers array.
     *
     * @return array Array of handlers.
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Returns the handlers.
     *
     * @return array Array of handler events.
     */
    public function getHandlerKeys(): array
    {
        return array_keys($this->handlers);
    }

    /**
     * Removes a handler.
     *
     * @param string $event The event handler to remove.
     */
    public function removeHandler(string $event): void
    {
        unset($this->handlers[$event]);
    }
}
