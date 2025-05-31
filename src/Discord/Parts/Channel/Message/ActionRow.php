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
 * An Action Row is a top-level layout component used in messages and modals.
 *
 * Action Rows can contain:

 * Up to 5 contextually grouped buttons
 * A single text input
 * A single select component (string select, user select, role select, mentionable select, or channel select)
 *
 * @link https://discord.com/developers/docs/components/reference#action-row
 *
 * @since 10.11.0
 *
 * @property int                               $type       1 for action row component
 * @property string|null                       $id         Optional identifier for component
 * @property ExCollectionInterface|Component[] $components Up to 5 interactive button components or a single select component
 */
class ActionRow extends Layout
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'components',
    ];
}
