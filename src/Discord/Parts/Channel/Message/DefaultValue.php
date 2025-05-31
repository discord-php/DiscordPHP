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

use JsonSerializable;

/**
 * List of default values for auto-populated select menu components; number of default values must be in the range defined by min_values and max_values
 *
 * @link https://discord.com/developers/docs/components/reference#user-select-select-default-value-structure
 *
 * @since 10.11.0
 *
 * @property string $id   ID of a user, role, or channel.
 * @property string $type Type of value that id represents. Either "user", "role", or "channel"
 */
class DefaultValue implements JsonSerializable
{
    public const TYPE_USER    = 'user';
    public const TYPE_ROLE    = 'role';
    public const TYPE_CHANNEL = 'channel';

    /** @var string */
    protected $id;

    /** @var string */
    protected $type;

    public function __construct(string $id, string $type)
    {
        $this->id = $id;
        $this->setType($type);
    }

    public static function new(string $id, string $type): self
    {
        return new self($id, $type);
    }

    public static function User(string $id): self
    {
        return new self($id, self::TYPE_USER);
    }

    public static function Role(string $id): self
    {
        return new self($id, self::TYPE_ROLE);
    }

    public static function Channel(string $id): self
    {
        return new self($id, self::TYPE_CHANNEL);
    }

    protected function setType(string $type): void
    {
        $allowed = [self::TYPE_USER, self::TYPE_ROLE, self::TYPE_CHANNEL];

        if (!in_array($type, $allowed, true)) {
            throw new \InvalidArgumentException('Default value type must be one of: "user", "role", or "channel"');
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
        ];
    }
}
