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

namespace Discord\Parts\OAuth;

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * If the app belongs to a team, this will be a list of the members of that team.
 *
 * @link https://discord.com/developers/docs/topics/teams#data-models-team-object
 *
 * @since 10.24.0
 *
 * @property string                                         $id            Unique ID of the team.
 * @property string|null                                    $icon          Hash of the image of the team's icon.
 * @property ExCollectionInterface<TeamMember>|TeamMember[] $members       Members of the team (array of team member objects).
 * @property string                                         $name          Name of the team.
 * @property string                                         $owner_user_id User ID of the current team owner.
 */
class Team extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'icon',
        'members',
        'name',
        'owner_user_id',
    ];

    /**
     * Returns the members attribute.
     *
     * @return ExCollectionInterface<TeamMember>|TeamMember[] A collection of team members.
     */
    protected function getMembersAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('members', TeamMember::class);
    }
}
