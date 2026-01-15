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

namespace Discord\Parts\Guild\AutoModeration;

use Discord\Parts\Part;

/**
 * An action which will execute whenever a rule is triggered.
 *
 * @link https://discord.com/developers/docs/resources/auto-moderation#auto-moderation-action-object
 *
 * @since 7.1.0
 *
 * @property int                 $type     The type of action.
 * @property ActionMetadata|null $metadata Additional metadata needed during execution for this specific action type.
 */
class Action extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'type',
        'metadata',
    ];

    /** Blocks a member's message and prevents it from being posted. A custom explanation can be specified and shown to members whenever their message is blocked. */
    public const TYPE_BLOCK_MESSAGE = 1;
    /** Logs user content to a specified channel. */
    public const TYPE_SEND_ALERT_MESSAGE = 2;
    /**
     * Timeout user for a specified duration.
     *
     * TIMEOUT action can only be set up for KEYWORD and MENTION_SPAM rules.
     * The MODERATE_MEMBERS permission is required to use the TIMEOUT action type.
     */
    public const TYPE_TIMEOUT = 3;

    /** Prevents a member from using text, voice, or other interactions. */
    public const TYPE_BLOCK_MEMBER_INTERACTION = 4;

    /**
     * Get the Metadata Attributes.
     *
     * @return ?ActionMetadata
     */
    public function getMetadataAttribute(): ?ActionMetadata
    {
        return $this->attributePartHelper('metadata', ActionMetadata::class);
    }

    /**
     * @inheritDoc
     *
     * @see Rule::getCreatableAttributes()
     */
    public function getCreatableAttributes(): array
    {
        return [
            'type' => $this->type,
        ] + $this->makeOptionalAttributes(['metadata']);
    }
}
