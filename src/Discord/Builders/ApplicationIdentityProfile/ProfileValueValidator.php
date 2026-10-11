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

use function Discord\poly_strlen;

/**
 * Shared outbound stat value rules for primary and dynamic fields.
 *
 * @internal
 */
final class ProfileValueValidator
{
    /** Require a text value within the documented character limit. */
    public function text($value, int $limit, string $field): void
    {
        if (! is_string($value)) {
            throw new \InvalidArgumentException($field.' must be a string.');
        }
        if (poly_strlen($value) > $limit) {
            throw new \LengthException($field.' cannot exceed '.$limit.' characters.');
        }
    }

    /** Require a finite number, without undocumented range restrictions. */
    public function number($value): void
    {
        if (! in_array(gettype($value), ['integer', 'double'], true)) {
            throw new \InvalidArgumentException('Stat values must be finite numbers.');
        }
        if (! is_finite((float) $value)) {
            throw new \InvalidArgumentException('Stat values must be finite numbers.');
        }
    }

    /** Require an integer stat, without coercing floats or numeric strings. */
    public function integer($value): void
    {
        if (! is_int($value)) {
            throw new \InvalidArgumentException('Stat value must be an integer.');
        }
    }

    /** Require one HTTP(S) media URL; validation never fetches it. */
    public function media($value): void
    {
        if (! is_array($value) || array_keys($value) !== ['url']) {
            throw new \InvalidArgumentException('Media requires an HTTP(S) url.');
        }
        $url = filter_var($value['url'], FILTER_VALIDATE_URL);
        if ($url === false) {
            throw new \InvalidArgumentException('Media requires an HTTP(S) url.');
        }
        if (! in_array(strtolower(parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Media requires an HTTP(S) url.');
        }
    }
}
