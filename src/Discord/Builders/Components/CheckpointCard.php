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

namespace Discord\Builders\Components;

/**
 * TODO
 *
 * @link https://discord.com/developers/docs/components/reference#checkpoint-card
 *
 * @property int $type 20 for Checkpoint Card component.
 */
class CheckpointCard extends ComponentObject
{
    public const USAGE = [];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = Component::TYPE_CHECKPOINT_CARD;

    /**
     * Creates a new checkpoint card.
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
        ];

        return $content;
    }
}
