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

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * An array containing a pinned messages and its pinned_at timestamp.
 *
 * @link https://discord.com/developers/docs/resources/message#message-pin-object
 *
 * @since 10.19.0
 *
 * @property ExCollectionInterface|MessagePin[] $items
 * @property bool                               $has_more
 */
class MessagePinData extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'items',
        'has_more',
    ];

     /** @return ExCollectionInterface<MessagePin> */
    protected function getItemsAttribute(): ExCollectionInterface
    {
        $collection = Collection::for(MessagePin::class);

        foreach ($this->attributes['items'] as $item) {
            $collection->pushItem($this->createOf(MessagePin::class, $item));
        }

        return $collection;
    }
}
