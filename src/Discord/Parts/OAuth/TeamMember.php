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

namespace Discord\Parts\OAuth;

use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Member of a team.
 *
 * @link https://discord.com/developers/docs/topics/teams#data-models-team-member-object
 *
 * @since 10.24.0
 *
 * @property int    $membership_state User's membership state on the team (1 = Invited, 2 = Accepted).
 * @property string $team_id          ID of the parent team of which they are a member.
 * @property User   $user             Partial user object (avatar, discriminator, ID, username).
 * @property string $role             Role of the team member (Owner, Admin, Developer, or Read-only).
 */
class TeamMember extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'membership_state',
        'team_id',
        'user',
        'role',
    ];

    /**
     * Returns the user attribute.
     *
     * @return User A partial user object.
     */
    protected function getUserAttribute(): User
    {
        return $this->attributePartHelper('user', User::class);
    }
}
