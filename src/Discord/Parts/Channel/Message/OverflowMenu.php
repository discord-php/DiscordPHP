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

use Discord\Helpers\ExCollectionInterface;

/**
 * A received overflow menu nested in an action row or section accessory.
 *
 * @property      int                                      $type   Component type 25.
 * @property      int|null                                 $id     Optional numeric component ID.
 * @property-read ExCollectionInterface<OverflowMenuGroup> $groups Groups in display order.
 */
class OverflowMenu extends Component
{
    protected $fillable = ['type', 'id', 'groups'];

    /**
     * Hydrates the message payload groups in display order.
     *
     * @return ExCollectionInterface<OverflowMenuGroup>
     */
    protected function getGroupsAttribute(): ExCollectionInterface
    {
        // Groups have no Discord ID. Keep their display order.
        return $this->attributeCollectionHelper('groups', OverflowMenuGroup::class, null);
    }
}
