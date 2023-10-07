<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\AutoModeration\Rule;
use Discord\Repository\AbstractRepository;

/**
 * Contains auto moderation rules for a guild.
 *
 * @see Rule
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 7.1.0
 *
 * @method Rule|null get(string $discrim, $key)
 * @method Rule|null pull(string|int $key, $default = null)
 * @method Rule|null first()
 * @method Rule|null last()
 * @method Rule|null find(callable $callback)
 */
class AutoModerationRuleRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_AUTO_MODERATION_RULES,
        'get' => Endpoint::GUILD_AUTO_MODERATION_RULE,
        'create' => Endpoint::GUILD_AUTO_MODERATION_RULES,
        'update' => Endpoint::GUILD_AUTO_MODERATION_RULE,
        'delete' => Endpoint::GUILD_AUTO_MODERATION_RULE,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Rule::class;
}
