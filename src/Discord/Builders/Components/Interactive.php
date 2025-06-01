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

namespace Discord\Builders\Components;

/**
  * @since 10.9.0
  */
abstract class Interactive extends ComponentObject
{
    /**
     * Custom ID to send with interactive component.
     *
     * @var string|null
     */
    protected $custom_id;


    /**
     * Retrieves the ID associated with the interactive component.
     * Always returns null as this component does not have an ID.
     *
     * @return null
     */
    protected function getId()
    {
        return null;
    }
}
