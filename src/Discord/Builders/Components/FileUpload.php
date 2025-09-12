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
 * TODO.
 *
 * @link TODO
 *
 * @since 10.21.0
 *
 * @property int        $type       19 for File Upload component.
 * @property string     $custom_id  ID for the select menu; max 100 characters.
 * @property ?int|null  $min_values Minimum number of items that must be chosen (defaults to 1); min 0, max 5.
 * @property ?int|null  $max_values Maximum number of items that can be chosen (defaults to 1); max 5.
 * @property ?bool|null $required   Whether this component is required to be filled (defaults to true).
 */
class FileUpload extends SelectMenu
{
    public const USAGE = ['Modal'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = Component::TYPE_FILE_UPLOAD;

    /**
     * Set if this component is required to be filled, default false. (Modal only).
     *
     * @param bool|null $required
     *
     * @return $this
     */
    public function setRequired(?bool $required = null): self
    {
        $this->required = $required;

        return $this;
    }
}
