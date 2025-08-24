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
 * Select menu for users.
 *
 * @link https://discord.com/developers/docs/components/reference#user-select
 *
 * @since 10.0.0
 */
class UserSelect extends SelectMenu
{
    public const USAGE = ['Message'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = Component::TYPE_USER_SELECT;

    /**
     * Set if this component is required to be filled, default false. (Modal only).
     *
     * @param bool $required
     *
     * @return $this
     */
    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }
}
