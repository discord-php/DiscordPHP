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

namespace Discord\Voice;

use Discord\Parts\Part;

/**
 * Voice region object that can be used when setting a voice or stage channel's rtc_region.
 *
 * @property string $id         Unique ID for the region.
 * @property string $name       Name of the region.
 * @property bool   $optimal    True for a single server that is closest to the current user's client.
 * @property bool   $deprecated Whether this is a deprecated voice region (avoid switching to these).
 * @property bool   $custom     Whether this is a custom voice region (used for events/etc).
 */
class Region extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'name',
        'optimal',
        'deprecated',
        'custom',
    ];
}
