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

namespace Discord\Parts\Channel\Message;

use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Represents metadata about the interaction that caused a message.
 *
 * @link https://discord.com/developers/docs/resources/message#message-interaction-metadata-object
 *
 * @property string                          $id                              ID of the interaction.
 * @property int                             $type                            Type of interaction.
 * @property User                            $user                            User who triggered the interaction.
 * @property array                           $authorizing_integration_owners  IDs for installation context(s) related to the interaction.
 * @property string|null                     $original_response_message_id    ID of the original response message (follow-ups only).
 * @property User|null                       $target_user                     The user the command was run on (user command interactions only).
 * @property string|null                     $target_message_id               The ID of the message the command was run on (message command interactions only).
 * @property string|null                     $interacted_message_id           The ID of the message that contained the interactive component (message component interactions only).
 * @property MessageInteractionMetadata|null $triggering_interaction_metadata Metadata for the interaction that was used to open the modal (modal submit interactions only).
 */
class MessageInteractionMetadata extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'type',
        'user',
        'authorizing_integration_owners',
        'original_response_message_id',
        'target_user',
        'target_message_id',
        'interacted_message_id',
        'triggering_interaction_metadata',
    ];

    /**
     * Returns the user who triggered the interaction.
     *
     * @return User
     */
    protected function getUserAttribute(): User
    {
        return $this->factory->part(User::class, (array) $this->attributes['user'], true);
    }

    /**
     * Returns the target user (if present).
     *
     * @return User|null
     */
    protected function getTargetUserAttribute(): ?User
    {
        if (!isset($this->attributes['target_user'])) {
            return null;
        }

        return $this->factory->part(User::class, (array) $this->attributes['target_user'], true);
    }

    /**
     * Returns the triggering interaction metadata (for modal submit interactions).
     *
     * @return MessageInteractionMetadata|null
     */
    protected function getTriggeringInteractionMetadataAttribute(): ?MessageInteractionMetadata
    {
        if (!isset($this->attributes['triggering_interaction_metadata'])) {
            return null;
        }

        return $this->factory->part(self::class, (array) $this->attributes['triggering_interaction_metadata'], true);
    }
}
