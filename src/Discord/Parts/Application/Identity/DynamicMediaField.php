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

namespace Discord\Parts\Application\Identity;

/**
 * A custom image stat.
 *
 * @since 10.59.0
 * @property ProfileMedia|null $value Media value.
 */
class DynamicMediaField extends DynamicField
{
    /** @return ProfileMedia|null */
    protected function getValueAttribute(): ?ProfileMedia
    {
        if (! isset($this->attributes['value'])) {
            return null;
        }

        return $this->attributes['value'] instanceof ProfileMedia
            ? $this->attributes['value']
            : $this->createOf(ProfileMedia::class, $this->attributes['value']);
    }
}
