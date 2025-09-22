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

use Discord\Parts\Part;

/**
 * Additional data used to determine whether a rule should be triggered. Different fields are relevant based on the value of trigger_type.
 *
 * @link https://discord.com/developers/docs/resources/auto-moderation#auto-moderation-rule-object-trigger-metadata
 *
 * @since 10.24.0
 *
 * @property array|string[] $keyword_filter                  Substrings which will be searched for in content (Maximum of 1000).
 * @property array|string[] $regex_patterns                  Regular expression patterns which will be matched against content (Maximum of 10).
 * @property array|string[] $presets                         The internally pre-defined wordsets which will be searched for in content.
 * @property array|string[] $allow_list                      Substrings which should not trigger the rule (Maximum of 100 or 1000).
 * @property int            $mention_total_limit             Total number of unique role and user mentions allowed per message (Maximum of 50).
 * @property bool           $mention_raid_protection_enabled Whether to automatically detect mention raids.
 */
class TriggerMetadata extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'keyword_filter',
        'regex_patterns',
        'presets',
        'allow_list',
        'mention_total_limit',
        'mention_raid_protection_enabled',
    ];
}
