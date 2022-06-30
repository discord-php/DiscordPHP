<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\WebSockets;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\AutoModeration\Action;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * Sent when a rule is triggered and an action is executed (e.g. when a message is blocked).
 *
 * @see https://discord.com/developers/docs/topics/gateway#auto-moderation-action-execution-auto-moderation-action-execution-event-fields
 *
 * @property string       $guild_id                The id of the guild in which action was executed.
 * @property Guild|null   $guild                   The guild in which action was executed.
 * @property Action       $action                  The action which was executed.
 * @property string       $rule_id                 The id of the rule which action belongs to.
 * @property int          $rule_trigger_type       The trigger type of rule which was triggered.
 * @property string       $user_id                 The id of the user which generated the content which triggered the rule.
 * @property User|null    $user                    The user which generated the content which triggered the rule.
 * @property Member|null  $member                  Cached member which generated the content which triggered the rule.
 * @property string|null  $channel_id              The id of the channel in which user content was posted.
 * @property Channel|null $channel                 Cached channel in which user content was posted.
 * @property string|null  $message_id              The id of any user message which content belongs to (will not exist if message was blocked by automod or content was not part of any message)
 * @property Message|null $message                 Cached user message which content belongs to (will not exist if message was blocked by automod or content was not part of any message)
 * @property string|null  $alert_system_message_id The id of any system auto moderation messages posted as a result of this action (will not exist if this event does not correspond to an action with type `SEND_ALERT_MESSAGE`)
 * @property Message|null $alert_system_message    Cached system auto moderation messages posted as a result of this action.
 * @property string       $content                 The user generated text content.
 * @property string|null  $matched_keyword         The word or phrase configured in the rule that triggered the rule. (empty without message content intent)
 * @property string|null  $matched_content         The substring in content that triggered the rule. (empty without message content intent)
 */
class AutoModerationActionExecution extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'guild_id',
        'action',
        'rule_id',
        'rule_trigger_type',
        'user_id',
        'channel_id',
        'message_id',
        'alert_system_message_id',
        'content',
        'matched_keyword',
        'matched_content',
    ];

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild in which action was executed.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->offsetGet($this->guild_id);
    }

    /**
     * Returns the action attribute.
     *
     * @return Action The action which was executed.
     */
    protected function getActionAttribute(): Action
    {
        return $this->factory->create(Action::class, $this->attributes['action'], true);
    }

    /**
     * Returns the user attribute.
     *
     * @return User|null The user which generated the content which triggered the rule.
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }

    /**
     * Returns the member attribute.
     *
     * @return Member|null Cached member which generated the content which triggered the rule.
     */
    protected function getMemberAttribute(): ?Member
    {
        if ($guild = $this->guild) {
            return $guild->members->get('id', $this->user_id);
        }

        return null;
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel|null Cached channel in which user content was posted.
     */
    protected function getChannelAttribute(): ?Channel
    {
        if (isset($this->channel_id) && $guild = $this->guild) {
            return $guild->channels->get('id', $this->channel_id);
        }

        return null;
    }

    /**
     * Returns the message attribute.
     *
     * @return Message|null Cached channel in which user content was posted.
     */
    protected function getMessageAttribute(): ?Message
    {
        if ($channel = $this->channel) {
            return $channel->messages->get('id', $this->message_id);
        }

        return null;
    }

    /**
     * Returns the alert system message attribute.
     *
     * @return Message|null Cached system auto moderation messages posted as a result of this action.
     */
    protected function getAlertSystemMessageAttribute(): ?Message
    {
        if ($channel = $this->channel) {
            return $channel->messages->get('id', $this->alert_system_message_id);
        }

        return null;
    }
}
