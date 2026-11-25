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

namespace Discord\Parts\Channel\Message;

/**
 * An overflow menu in a message.
 *
 * @property int                     $type
 * @property int|null                $id
 * @property-read OverflowMenuGroup[] $groups
 */
class OverflowMenu extends Component
{
    protected $fillable = ['type', 'id', 'groups'];

    /** @return list<OverflowMenuGroup> */
    protected function getGroupsAttribute(): array
    {
        $groups = $this->attributes['groups'] ?? [];

        foreach ($groups as &$group) {
            if (! $group instanceof OverflowMenuGroup) {
                $group = $this->createOf(OverflowMenuGroup::class, $group);
            }
        }
        $this->attributes['groups'] = $groups;

        return array_values($groups);
    }
}
