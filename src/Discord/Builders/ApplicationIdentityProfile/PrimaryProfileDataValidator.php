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

namespace Discord\Builders\ApplicationIdentityProfile;

use Discord\Parts\Application\Identity\PrimaryProfileData;

/**
 * Outbound primary stat schema and value validation.
 *
 * @internal
 */
final class PrimaryProfileDataValidator
{
    /** Validate all supplied primary fields; missing fields remain omitted. */
    public function validate($primary): void
    {
        if (! is_array($primary)) {
            throw new \InvalidArgumentException('primary must be an object.');
        }
        $values = new ProfileValueValidator();
        $validators = array_fill_keys(PrimaryProfileData::STRING_FIELDS, static fn ($value) => $values->text($value, 100, 'primary value'))
            + array_fill_keys(PrimaryProfileData::MEDIA_FIELDS, $values->media(...))
            + array_fill_keys(PrimaryProfileData::INTEGER_FIELDS, $values->integer(...))
            + ['playtime_hours' => $values->number(...)];
        foreach ($primary as $key => $value) {
            $validator = $validators[$key] ?? null;
            if ($validator === null) {
                throw new \InvalidArgumentException('Unknown primary field: '.$key);
            }
            $validator($value);
        }
    }
}
