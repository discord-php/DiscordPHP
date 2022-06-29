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
 * Contains auto moderation rules that belong to guilds.
 *
 * @see \Discord\Parts\Guild\AutoModeration\Rule
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Rule|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Rule|null first()                     Returns the first element of the collection.
 * @method Rule|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Rule|null find(callable $callback)    Runs a filter callback over the repository.
 */
class AutoModerationRuleRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_AUTO_MODERATION_RULES,
        'get' => Endpoint::GUILD_AUTO_MODERATION_RULE,
        'create' => Endpoint::GUILD_AUTO_MODERATION_RULES,
        'update' => Endpoint::GUILD_AUTO_MODERATION_RULE,
        'delete' => Endpoint::GUILD_AUTO_MODERATION_RULE,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Rule::class;
}
