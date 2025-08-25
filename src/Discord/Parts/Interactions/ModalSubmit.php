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

use Discord\Parts\Interactions\Request\ModalSubmitData;

/**
 * @since 10.19.0
 *
 * @property ModalSubmitData $data Data associated with the interaction.
 */
class ModalSubmit extends Interaction
{
    /**
     * Type of the interaction.
     *
     * @var int
     */
    protected $type = Interaction::TYPE_MODAL_SUBMIT;

    /**
     * The data for the application command interaction.
     *
     * @var ModalSubmitData
     */
    protected $data;

    /**
     * Returns the data associated with the interaction.
     *
     * @return ModalSubmitData
     */
    protected function getDataAttribute(): ModalSubmitData
    {
        $adata = $this->attributes['data'];
        if (! isset($adata->guild_id) && isset($this->attributes['guild_id'])) {
            $adata->guild_id = $this->guild_id;
        }

        return $this->createOf(ModalSubmitData::class, $adata);
    }
}
