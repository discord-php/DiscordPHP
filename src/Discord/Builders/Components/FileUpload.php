<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders\Components;

/**
 * A File Upload is an interactive component that allows users to upload files in modals.
 *
 * @link https://discord.com/developers/docs/components/reference#file-upload
 *
 * @since 10.21.0
 *
 * @property int        $type       19 for File Upload component.
 * @property string     $custom_id  ID for the file upload; max 100 characters.
 * @property ?int|null  $min_values Minimum number of files that must be uploaded (defaults to 1); min 0, max 10.
 * @property ?int|null  $max_values Maximum number of files that can be uploaded (defaults to 1); max 10.
 * @property ?bool|null $required   Whether the file upload is required to be filled in a modal (defaults to `true`).
 */
class FileUpload extends Interactive
{
    public const USAGE = ['Modal'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = ComponentObject::TYPE_FILE_UPLOAD;

    /**
     * Minimum number of files that can be uploaded.
     * Default 0, maximum 10.
     *
     * @var int|null
     */
    protected $min_values;

    /**
     * Maximum number of files that can be uploaded.
     * Default 1, maximum 10.
     *
     * @var int|null
     */
    protected $max_values;

    /**
     * Creates a new file upload.
     *
     * @param string|null $custom_id custom ID of the file upload. If not given, a UUID will be used
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(?string $custom_id = null)
    {
        $this->setCustomId($custom_id ?? self::generateUuid());
    }

    /**
     * Creates a new file upload component.
     *
     * @param string $custom_id ID for the file upload.
     *
     * @return self
     */
    public static function new(?string $custom_id = null): self
    {
        return new self($custom_id);
    }

    /**
     * Sets the minimum number of files which must be uploaded.
     *
     * @param int|null $min_values Default `1`, minimum `0` and maximum `10`. `null` to set as default.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setMinValues(?int $min_values): self
    {
        if (isset($min_values) && ($min_values < 1 || $min_values > 10)) {
            throw new \LengthException('Number must be between 0 and 10 inclusive.');
        }

        $this->min_values = $min_values;

        return $this;
    }

    /**
     * Sets the maximum number of files which can be uploaded.
     *
     * @param int|null $max_values Default `1` and maximum `10`. `null` to set as default.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setMaxValues(?int $max_values): self
    {
        if ($max_values && $max_values > 10) {
            throw new \LengthException('Number must be less than or equal to 10.');
        }

        $this->max_values = $max_values;

        return $this;
    }

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

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
            'custom_id' => $this->custom_id,
        ];

        if (isset($this->min_values)) {
            $content['min_values'] = $this->min_values;
        }

        if (isset($this->max_values)) {
            $content['max_values'] = $this->max_values;
        }

        if (isset($this->required)) {
            $content['required'] = $this->required;
        }

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        return $content;
    }
}
