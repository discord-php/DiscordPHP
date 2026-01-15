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

namespace Discord\Parts\OAuth;

use Discord\Parts\Part;

/**
 * A representation of role connection metadata for an application.
 *
 * Each metadata type offers a comparison operation that allows guilds to configure role requirements based on metadata values stored by the bot.
 * Bots specify a metadata value for each user and guilds specify the required guild's configured value within the guild role settings.
 *
 * @link https://discord.com/developers/docs/resources/application-role-connection-metadata#application-role-connection-metadata
 *
 * @since 10.29.0
 *
 * @property int        $type                      Type of metadata value (see ApplicationRoleConnectionMetadataType).
 * @property string     $key                       Dictionary key for the metadata field (a-z, 0-9, or _; 1-50 characters).
 * @property string     $name                      Name of the metadata field (1-100 characters).
 * @property array|null $name_localizations        Translations of the name (dictionary with keys in available locales).
 * @property string     $description               Description of the metadata field (1-200 characters).
 * @property array|null $description_localizations Translations of the description (dictionary with keys in available locales).
 */
class ApplicationRoleConnectionMetadata extends Part
{
    /** The metadata value (integer) is less than or equal to the guild's configured value (integer). */
    public const TYPE_INTEGER_LESS_THAN_OR_EQUAL = 1;
    /** The metadata value (integer) is less than or equal to the guild's configured value (integer). */
    public const TYPE_INTEGER_GREATER_THAN_OR_EQUAL = 2;
    /** The metadata value (integer) is equal to the guild's configured value (integer). */
    public const TYPE_INTEGER_EQUAL = 3;
    /** The metadata value (integer) is not equal to the guild's configured value (integer). */
    public const TYPE_INTEGER_NOT_EQUAL = 4;
    /** The metadata value (ISO8601 string) is less than or equal to the guild's configured value (integer; days before current date). */
    public const TYPE_DATETIME_LESS_THAN_OR_EQUAL = 5;
    /** The metadata value (ISO8601 string) is greater than or equal to the guild's configured value (integer; days before current date). */
    public const TYPE_DATETIME_GREATER_THAN_OR_EQUAL = 6;
    /** The metadata value (integer) is equal to the guild's configured value (integer; 1). */
    public const TYPE_BOOLEAN_EQUAL = 7;
    /** The metadata value (integer) is not equal to the guild's configured value (integer; 1). */
    public const TYPE_BOOLEAN_NOT_EQUAL = 8;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'type',
        'key',
        'name',
        'name_localizations',
        'description',
        'description_localizations',
    ];
}
