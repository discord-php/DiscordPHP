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

namespace Discord\Parts\Interactions\Request;

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Message\Component;

/**
 * Represents the data associated with an interaction.
 *
 * @link https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-modal-submit-data-structure
 *
 * @since 10.19.0
 *
 * @property string                            $custom_id  Custom ID the component was created for.
 * @property ExCollectionInterface|Component[] $components The values submitted by the user.
 */
class ModalSubmitData extends InteractionData
{
    protected $fillable = [
        'custom_id',
        'components',
    ];
}
