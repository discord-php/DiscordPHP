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

namespace Discord\Parts\Interactions;

/**
 * @since 10.19.0
 *
 * @property null $data Data associated with the interaction.
 */
class Ping extends Interaction
{
    /**
     * Type of the interaction.
     *
     * @var int
     */
    protected $type = Interaction::TYPE_PING;

    /**
     * Returns the data associated with the interaction.
     */
    protected function getDataAttribute(): null
    {
        return null;
    }
}
