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

namespace Discord\Parts\Guild;

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * Represents the response from listing guild join requests.
 *
 * @since 10.55.0
 *
 * @property int                                                        $total               Number of join requests with the given status (when returned).
 * @property ExCollectionInterface<GuildJoinRequest>|GuildJoinRequest[] $guild_join_requests Array of join request objects.
 */
class GuildJoinRequests extends Part
{
    /** @inheritDoc */
    protected $fillable = [
        'total',
        'guild_join_requests',
    ];

    /**
     * Returns a collection of `GuildJoinRequest` parts.
     *
     * @return ExCollectionInterface<GuildJoinRequest>
     */
    protected function getGuildJoinRequestsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('guild_join_requests', GuildJoinRequest::class);
    }
}
