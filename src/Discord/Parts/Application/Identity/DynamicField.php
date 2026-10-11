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

namespace Discord\Parts\Application\Identity;

use Discord\Parts\Part;

/**
 * A custom game stat. Unknown future types retain their raw value.
 *
 * @since 10.59.0
 * @link https://docs.discord.com/developers/resources/application-identity-profile#dynamic-field-object
 *
 * @property int    $type  Value discriminator.
 * @property string $name  Widget data key.
 * @property mixed  $value Stat value.
 */
class DynamicField extends Part
{
    public const TYPE_STRING = 1;
    public const TYPE_NUMBER = 2;
    public const TYPE_MEDIA = 3;
    public const TYPES = [
        0 => self::class,
        self::TYPE_STRING => DynamicStringField::class,
        self::TYPE_NUMBER => DynamicNumberField::class,
        self::TYPE_MEDIA => DynamicMediaField::class,
    ];

    protected $fillable = ['type', 'name', 'value'];

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        return $this->getRawAttributes();
    }
}
