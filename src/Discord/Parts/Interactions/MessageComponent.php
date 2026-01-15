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

namespace Discord\Parts\Interactions;

use Discord\Parts\Interactions\Request\MessageComponentData;

/**
 * @since 10.19.0
 *
 * @property MessageComponentData $data Data associated with the interaction.
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
     * @return MessageComponentData
     */
    protected function getDataAttribute(): MessageComponentData
    {
        $adata = $this->attributes['data'];
        if (! isset($adata->guild_id) && isset($this->attributes['guild_id'])) {
            $adata->guild_id = $this->guild_id;
        }

        return $this->createOf(MessageComponentData::class, $adata);
    }
}
