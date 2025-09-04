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

use Discord\Parts\Interactions\Request\ApplicationCommandData;

/**
 * @since 10.19.0
 *
 * @property ApplicationCommandData $data Data associated with the interaction.
 */
class ApplicationCommand extends Interaction
{
    /**
     * Type of the interaction.
     *
     * @var int
     */
    protected $type = Interaction::TYPE_APPLICATION_COMMAND;

    /**
     * Returns the data associated with the interaction.
     *
     * @return ApplicationCommandData
     */
    protected function getDataAttribute(): ApplicationCommandData
    {
        $adata = $this->attributes['data'];
        if (! isset($adata->guild_id) && isset($this->attributes['guild_id'])) {
            $adata->guild_id = $this->guild_id;
        }

        return $this->createOf(ApplicationCommandData::class, $adata);
    }
}
