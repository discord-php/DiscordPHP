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

use Discord\Parts\Application\Identity\DynamicField;

/**
 * Outbound dynamic field list, discriminators, and values.
 *
 * @internal
 */
final class DynamicProfileDataValidator
{
    /** Validate an ordered list of at most 30 custom fields. */
    public function validate($dynamic): void
    {
        if (! is_array($dynamic) || ! array_is_list($dynamic)) {
            throw new \InvalidArgumentException('dynamic must be a list.');
        }
        if (count($dynamic) > 30) {
            throw new \LengthException('At most 30 dynamic fields are allowed.');
        }
        foreach ($dynamic as $field) {
            $this->field($field);
        }
    }

    /** Dispatch value validation using the root family's discriminator constants. */
    private function field($field): void
    {
        if (! is_array($field)) {
            throw new \InvalidArgumentException('Invalid dynamic field shape.');
        }
        $values = new ProfileValueValidator();
        $validators = [
            DynamicField::TYPE_STRING => static fn ($value) => $values->text($value, 100, 'value'),
            DynamicField::TYPE_NUMBER => $values->number(...),
            DynamicField::TYPE_MEDIA => $values->media(...),
        ];
        $type = $field['type'] ?? null;
        if (! is_int($type) || ! array_key_exists($type, $validators)) {
            throw new \InvalidArgumentException('Unknown dynamic field type.');
        }
        if (! array_key_exists('value', $field) || array_diff(array_keys($field), ['type', 'name', 'value'])) {
            throw new \InvalidArgumentException('Invalid dynamic field shape.');
        }
        $values->text($field['name'] ?? null, 100, 'name');
        $validators[$type]($field['value']);
    }
}
