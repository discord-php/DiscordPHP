<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts;

use stdClass;

/**
 * A metadata is a property of a Part that consists of shared properties and may
 * caontain new properties but strict on pre defined properties.
 *
 * @internal
 *
 * @since 10.0.0
 */
abstract class Metadata extends stdClass
{
    /**
     * Fill the properties from construtor.
     */
    public function __construct(?object $data = null)
    {
        if (null !== $data) {
            foreach ($data as $property => $value) {
                $this->$property = $value;
            }
        }
    }
}
