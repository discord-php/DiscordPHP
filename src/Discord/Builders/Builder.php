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

namespace Discord\Builders;

use Discord\Parts\Part;

abstract class Builder
{
    public static function fromPart(Part $part): self
    {
        $builder = new static();
        $attributes = $part->getRawAttributes();

        foreach ($attributes as $key => $value) {
            if (property_exists(self::class, $key)) {
                $builder->{$key} = $value;
            }
        }

        return $builder;
    }
}
