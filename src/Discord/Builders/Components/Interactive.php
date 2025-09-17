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

use function Discord\poly_strlen;

/**
 * @since 10.9.0
 */
abstract class Interactive extends ComponentObject
{
    /**
     * Custom ID to send with interactive component.
     *
     * @var string|null
     */
    protected $custom_id;

    /**
     * Retrieves the ID associated with the interactive component.
     * Always returns null as this component does not have an ID.
     */
    protected function getId()
    {
        return null;
    }

    /**
     * Sets the custom ID for the interactive component.
     *
     * @param string|null $custom_id
     *
     * @throws \LengthException If the custom ID is longer than 100 characters.
     *
     * @return $this
     */
    public function setCustomId(?string $custom_id): self
    {
        if (isset($custom_id) && poly_strlen($custom_id) > 100) {
            throw new \LengthException('Custom ID must be maximum 100 characters.');
        }

        $this->custom_id = $custom_id;

        return $this;
    }
}
