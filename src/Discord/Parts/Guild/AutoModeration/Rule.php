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

namespace Discord\Parts\Guild\AutoModeration;

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;

/**
 * Auto Moderation is a feature which allows each guild to set up rules that
 * trigger based on some criteria. For example, a rule can trigger whenever a
 * message contains a specific keyword.
 * Rules can be configured to automatically execute actions whenever they
 * trigger. For example, if a user tries to send a message which contains a
 * certain keyword, a rule can trigger and block the message before it is sent.
 *
 * @link https://discord.com/developers/docs/resources/auto-moderation#auto-moderation-rule-object
 *
 * @since 7.1.0
 *
 * @property      string                                   $id               The id of this rule.
 * @property      string                                   $guild_id         The id of the guild which this rule belongs to.
 * @property-read Guild|null                               $guild            The guild which this rule belongs to.
 * @property      string                                   $name             The rule name.
 * @property      string                                   $creator_id       The id of the user which first created this rule.
 * @property-read User|null                                $creator          The user which first created this rule.
 * @property      int                                      $event_type       The rule event type.
 * @property      int                                      $trigger_type     The rule trigger type.
 * @property      TriggerMetadata                          $trigger_metadata The rule trigger metadata (may contain `keyword_filter`, `regex_patterns`, `presets`, `allow_list`, `mention_total_limit` and `mention_raid_protection_enabled`).
 * @property      ExCollectionInterface<Action>|Action[]   $actions          The actions which will execute when the rule is triggered.
 * @property      bool                                     $enabled          Whether the rule is enabled.
 * @property      ExCollectionInterface<Role>|Role[]       $exempt_roles     The role ids that should not be affected by the rule (Maximum of 20).
 * @property      ExCollectionInterface<Channel>|Channel[] $exempt_channels  The channel ids that should not be affected by the rule (Maximum of 50).
 */
class Rule extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'guild_id',
        'name',
        'creator_id',
        'event_type',
        'trigger_type',
        'trigger_metadata',
        'actions',
        'enabled',
        'exempt_roles',
        'exempt_channels',
    ];

    /** Check if content contains words from a user defined list of keywords. */
    public const TRIGGER_TYPE_KEYWORD = 1;
    /** Check if content represents generic spam. */
    public const TRIGGER_TYPE_SPAM = 3;
    /** Check if content contains words from internal pre-defined wordsets. */
    public const TRIGGER_TYPE_KEYWORD_PRESET = 4;
    /** Check if content contains more unique mentions than allowed. */
    public const TRIGGER_TYPE_MENTION_SPAM = 5;
    /** Check if member profile contains words from a user defined list of keywords. */
    public const TRIGGER_TYPE_PROFILE = 6;

    /** Words that may be considered forms of swearing or cursing. */
    public const KEYWORD_PRESET_TYPE_PROFANITY = 1;
    /** Words that refer to sexually explicit behavior or activity. */
    public const KEYWORD_PRESET_TYPE_SEXUAL_CONTENT = 2;
    /** Personal insults or words that may be considered hate speech. */
    public const KEYWORD_PRESET_TYPE_SLURS = 3;

    /** When a member sends or edits a message in the guild. */
    public const EVENT_TYPE_MESSAGE_SEND = 1;
    /** When a member edits their profile. */
    public const EVENT_TYPE_MEMBER_UPDATE = 2;

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild the rule belongs to.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the user attribute.
     *
     * @return User|null The user which first created this rule.
     */
    protected function getCreatorAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->attributes['creator_id']);
    }

    /**
     * Returns the trigger metadata attribute.
     *
     * @return TriggerMetadata The rule trigger metadata.
     */
    protected function getTriggerMetadataAttribute(): TriggerMetadata
    {
        return $this->attributePartHelper('trigger_metadata', TriggerMetadata::class);
    }

    /**
     * Returns the actions attribute.
     *
     * @return ExCollectionInterface<Action>|Action[] A collection of actions.
     */
    protected function getActionsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('actions', Action::class);
    }

    /**
     * Returns the exempt roles attribute.
     *
     * @return ExCollectionInterface<Role>|Role[] A collection of roles exempt from the rule.
     */
    protected function getExemptRolesAttribute(): ExCollectionInterface
    {
        $roles = new Collection();

        if (empty($this->attributes['exempt_roles'])) {
            return $roles;
        }

        $roles->fill(array_fill_keys($this->attributes['exempt_roles'], null));

        if ($guild = $this->guild) {
            $roles->merge($guild->roles->filter(
                fn ($role) => in_array($role->id, $this->attributes['exempt_roles'])
            ));
        }

        return $roles;
    }

    /**
     * Returns the exempt channels attribute.
     *
     * @return ExCollectionInterface<Channel>|Channel[] A collection of channels exempt from the rule.
     */
    protected function getExemptChannelsAttribute(): ExCollectionInterface
    {
        $channels = new Collection();

        if (empty($this->attributes['exempt_channels'])) {
            return $channels;
        }

        $channels->fill(array_fill_keys($this->attributes['exempt_channels'], null));

        if ($guild = $this->guild) {
            $channels->merge($guild->channels->filter(
                fn ($channel) => in_array($channel->id, $this->attributes['exempt_channels'])
            ));
        }

        return $channels;
    }

    /**
     * @inheritDoc
     *
     * @link https://discord.com/developers/docs/resources/auto-moderation#create-auto-moderation-rule-json-params
     */
    public function getCreatableAttributes(): array
    {
        $attr = [
            'name' => $this->name,
            'event_type' => $this->event_type,
            'trigger_type' => $this->trigger_type,
            'actions' => array_values($this->actions->map(function (Action $action) {
                return $action->getCreatableAttributes();
            })->toArray()),
        ];

        $attr += $this->makeOptionalAttributes([
            'enabled' => $this->enabled,
            'exempt_roles',
            'exempt_channels',
        ]);

        if (in_array($this->trigger_type, [self::TRIGGER_TYPE_KEYWORD, self::TRIGGER_TYPE_KEYWORD_PRESET, self::TRIGGER_TYPE_MENTION_SPAM, self::TRIGGER_TYPE_PROFILE])) {
            $attr['trigger_metadata'] = $this->trigger_metadata;
        }

        return $attr;
    }

    /**
     * @inheritDoc
     *
     * @link https://discord.com/developers/docs/resources/auto-moderation#modify-auto-moderation-rule-json-params
     */
    public function getUpdatableAttributes(): array
    {
        $attr = [
            'name' => $this->name,
            'event_type' => $this->event_type,
            'actions' => $this->actions,
            'enabled' => $this->enabled,
            'exempt_roles' => $this->attributes['exempt_roles'],
            'exempt_channels' => $this->attributes['exempt_channels'],
        ];

        if (in_array($this->trigger_type, [self::TRIGGER_TYPE_KEYWORD, self::TRIGGER_TYPE_KEYWORD_PRESET])) {
            $attr['trigger_metadata'] = $this->trigger_metadata;
        }

        return $attr;
    }

    /**
     * @inheritDoc
     */
    public function save(?string $reason = null): PromiseInterface
    {
        if (isset($this->attributes['guild_id'])) {
            /** @var Guild $guild */
            $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);

            return $guild->autoModerationRules->save($this, $reason);
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
            'auto_moderation_rule_id' => $this->id,
        ];
    }
}
