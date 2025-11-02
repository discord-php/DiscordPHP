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
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Represents an entry in the audit log.
 *
 * @since 5.1.0
 *
 * @link https://discord.com/developers/docs/resources/audit-log#audit-log-entry-object
 *
 * @property      ?string               $target_id   ID of the affected entity (webhook, user, role, etc.).
 * @property      ExCollectionInterface $changes     Changes made to the target_id.
 * @property      ?string               $user_id     User or app that made the changes.
 * @property-read User|null             $user
 * @property      string                $id          ID of the entry.
 * @property      int                   $action_type Type of action that occurred.
 * @property      ?Options|null         $options     Additional info for certain action types.
 * @property      ?string|null          $reason      Reason for the change (1-512 characters).
 */
class Entry extends Part
{
    // AUDIT LOG ENTRY TYPES
    /** Server settings were updated. */
    public const GUILD_UPDATE = 1;
    /** Channel was created. */
    public const CHANNEL_CREATE = 10;
    /** Channel settings were updated. */
    public const CHANNEL_UPDATE = 11;
    /** Channel was deleted. */
    public const CHANNEL_DELETE = 12;
    /** Permission overwrite was added to a channel. */
    public const CHANNEL_OVERWRITE_CREATE = 13;
    /** Permission overwrite was updated for a channel. */
    public const CHANNEL_OVERWRITE_UPDATE = 14;
    /** Permission overwrite was deleted from a channel. */
    public const CHANNEL_OVERWRITE_DELETE = 15;
    /** Member was removed from server. */
    public const MEMBER_KICK = 20;
    /** Members were pruned from server. */
    public const MEMBER_PRUNE = 21;
    /** Member was banned from server. */
    public const MEMBER_BAN_ADD = 22;
    /** Server ban was lifted for a member. */
    public const MEMBER_BAN_REMOVE = 23;
    /** Member was updated in server. */
    public const MEMBER_UPDATE = 24;
    /** Member was added or removed from a role. */
    public const MEMBER_ROLE_UPDATE = 25;
    /** Member was moved to a different voice channel. */
    public const MEMBER_MOVE = 26;
    /** Member was disconnected from a voice channel. */
    public const MEMBER_DISCONNECT = 27;
    /** Bot user was added to server. */
    public const BOT_ADD = 28;
    /** Role was created. */
    public const ROLE_CREATE = 30;
    /** Role was edited. */
    public const ROLE_UPDATE = 31;
    /** Role was deleted. */
    public const ROLE_DELETE = 32;
    /** Server invite was created. */
    public const INVITE_CREATE = 40;
    /** Server invite was updated. */
    public const INVITE_UPDATE = 41;
    /** Server invite was deleted. */
    public const INVITE_DELETE = 42;
    /** Webhook was created. */
    public const WEBHOOK_CREATE = 50;
    /** Webhook properties or channel were updated. */
    public const WEBHOOK_UPDATE = 51;
    /** Webhook was deleted. */
    public const WEBHOOK_DELETE = 52;
    /** Emoji was created. */
    public const EMOJI_CREATE = 60;
    /** Emoji name was updated. */
    public const EMOJI_UPDATE = 61;
    /** Emoji was deleted. */
    public const EMOJI_DELETE = 62;
    /** Single message was deleted. */
    public const MESSAGE_DELETE = 72;
    /** Multiple messages were deleted. */
    public const MESSAGE_BULK_DELETE = 73;
    /** Message was pinned to a channel. */
    public const MESSAGE_PIN = 74;
    /** Message was unpinned from a channel. */
    public const MESSAGE_UNPIN = 75;
    /** App was added to server. */
    public const INTEGRATION_CREATE = 80;
    /** App was updated (as an example, its scopes were updated). */
    public const INTEGRATION_UPDATE = 81;
    /** App was removed from server. */
    public const INTEGRATION_DELETE = 82;
    /** Stage instance was created (stage channel becomes live). */
    public const STAGE_INSTANCE_CREATE = 83;
    /** Stage instance details were updated. */
    public const STAGE_INSTANCE_UPDATE = 84;
    /** Stage instance was deleted (stage channel no longer live). */
    public const STAGE_INSTANCE_DELETE = 85;
    /** Sticker was created. */
    public const STICKER_CREATE = 90;
    /** Sticker details were updated. */
    public const STICKER_UPDATE = 91;
    /** Sticker was deleted. */
    public const STICKER_DELETE = 92;
    /** Event was created. */
    public const GUILD_SCHEDULED_EVENT_CREATE = 100;
    /** Event was updated. */
    public const GUILD_SCHEDULED_EVENT_UPDATE = 101;
    /** Event was cancelled. */
    public const GUILD_SCHEDULED_EVENT_DELETE = 102;
    /** Thread was created in a channel. */
    public const THREAD_CREATE = 110;
    /** Thread was updated. */
    public const THREAD_UPDATE = 111;
    /** Thread was deleted. */
    public const THREAD_DELETE = 112;
    /** Permissions were updated for a command. */
    public const APPLICATION_COMMAND_PERMISSION_UPDATE = 121;
    /** Soundboard sound was created. */
    public const SOUNDBOARD_SOUND_CREATE = 130;
    /** Soundboard sound was updated. */
    public const SOUNDBOARD_SOUND_UPDATE = 131;
    /** Soundboard sound was deleted. */
    public const SOUNDBOARD_SOUND_DELETE = 132;
    /** Auto Moderation rule was created. */
    public const AUTO_MODERATION_RULE_CREATE = 140;
    /** Auto Moderation rule was updated. */
    public const AUTO_MODERATION_RULE_UPDATE = 141;
    /** Auto Moderation rule was deleted. */
    public const AUTO_MODERATION_RULE_DELETE = 142;
    /** Message was blocked by Auto Moderation. */
    public const AUTO_MODERATION_BLOCK_MESSAGE = 143;
    /** Message was flagged by Auto Moderation. */
    public const AUTO_MODERATION_FLAG_TO_CHANNEL = 144;
    /** Member was timed out by Auto Moderation. */
    public const AUTO_MODERATION_USER_COMMUNICATION_DISABLED = 145;
    /** Member was quarantined by Auto Moderation. */
    public const AUTO_MODERATION_QUARANTINE_USER = 146;
    /** Creator monetization request was created. */
    public const CREATOR_MONETIZATION_REQUEST_CREATED = 150;
    /** Creator monetization terms were accepted. */
    public const CREATOR_MONETIZATION_TERMS_ACCEPTED = 151;
    /** Guild Onboarding Question was created. */
    public const ONBOARDING_PROMPT_CREATE = 163;
    /** Guild Onboarding Question was updated. */
    public const ONBOARDING_PROMPT_UPDATE = 164;
    /** Guild Onboarding Question was deleted. */
    public const ONBOARDING_PROMPT_DELETE = 165;
    /** Guild Onboarding was created. */
    public const ONBOARDING_CREATE = 166;
    /** Guild Onboarding was updated. */
    public const ONBOARDING_UPDATE = 167;
    /** Guild Server Guide was created. */
    public const HOME_SETTINGS_CREATE = 190;
    /** Guild Server Guide was updated. */
    public const HOME_SETTINGS_UPDATE = 191;
    /** Guild Profile was updated. */
    public const GUILD_PROFILE_UPDATE = 211;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'target_id',
        'changes',
        'user_id',
        'id',
        'action_type',
        'options',
        'reason',
    ];

    /**
     * Returns the user who made the changes.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }

    /**
     * Returns a collection of changes.
     *
     * @link https://discord.com/developers/docs/resources/audit-log#audit-log-change-object
     *
     * @return ExCollectionInterface
     */
    protected function getChangesAttribute(): ExCollectionInterface
    {
        return new Collection($this->attributes['changes'] ?? [], 'key', null);
    }

    /**
     * Returns the options of the entry.
     *
     * @return Options
     */
    protected function getOptionsAttribute(): Options
    {
        return $this->attributePartHelper('options', Options::class);
    }
}
