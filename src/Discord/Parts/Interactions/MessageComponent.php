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

use Discord\Parts\Interactions\Request\InteractionData;

/**
 * @property InteractionData $data Data associated with the interaction.
 */
class MessageComponent extends Interaction
{
    /**
     * Type of the interaction.
     *
     * @var int
     */
    protected $type = Interaction::TYPE_MESSAGE_COMPONENT;

    /**
     * Returns the data associated with the interaction.
     *
     * @return InteractionData
     */
    protected function getDataAttribute(): InteractionData
    {
        $adata = $this->attributes['data'];
        if (! isset($adata->guild_id) && isset($this->attributes['guild_id'])) {
            $adata->guild_id = $this->guild_id;
        }

        return $this->createOf(InteractionData::class, $adata);
    }
}
