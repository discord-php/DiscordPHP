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
 * Abstract class for interactive components.
 *
 * @link https://discord.com/developers/docs/components/reference#anatomy-of-a-component-custom-id
 *
 * @since 10.11.0
 *
 * @property int         $type      The type of the component.
 * @property string|null $id        32 bit integer used as an optional identifier for component.
 * @property string      $custom_id Developer-defined identifier, max 100 characters
 */
abstract class Interactive extends Component
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'custom_id'
    ];
}
