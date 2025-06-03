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

/**
 * Abstract base class providing helper methods for builder classes.
 *
 * @since 10.12.0
 *
 * @author Valithor Obsidion <valithor@valgorithms.com>
 */
abstract class Builder
{
    /**
     * Creates a new instance of the builder from a given Part.
     *
     * @param Part $part
     *
     * @return self
     */
    public static function fromPart(Part $part): self
    {
        $builder = new static();

        $attributes = $part->getRawAttributes();

        foreach ($attributes as $key => $value) {
            if (property_exists($builder, $key)) {
                $builder->{$key} = $value;
            }
        }

        return $builder;
    }
}
