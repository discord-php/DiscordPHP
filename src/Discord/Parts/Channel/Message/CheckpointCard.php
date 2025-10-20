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

namespace Discord\Parts\Channel\Message;

/**
 * TODO
 *
 * @link https://discord.com/developers/docs/components/reference#checkpoint-card
 *
 * @property int $type 20 for Checkpoint Card component.
 */
class CheckpointCard extends Component
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
    ];
}
