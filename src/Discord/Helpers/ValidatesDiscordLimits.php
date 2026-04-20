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

namespace Discord\Helpers;

/**
 * Centralises Discord API payload size limits as shared constants.
 *
 * Classes that produce or validate Discord-bound payloads implement this
 * interface to expose consistent limits in a single location. Grouping
 * them prevents the "magic number" drift that happens when the same
 * limit is repeated across builders and parts.
 *
 * @link https://docs.discord.com/developers/resources/message#embed-object-embed-limits
 * @link https://docs.discord.com/developers/resources/message#create-message
 *
 * @since 10.48.0
 */
interface ValidatesDiscordLimits
{
    /** Maximum characters in an embed title. */
    public const EMBED_TITLE_MAX = 256;

    /** Maximum characters in an embed description. */
    public const EMBED_DESCRIPTION_MAX = 4096;

    /** Maximum characters in an embed author name. */
    public const EMBED_AUTHOR_NAME_MAX = 256;

    /** Maximum characters in an embed footer text. */
    public const EMBED_FOOTER_TEXT_MAX = 2048;

    /** Maximum characters in an embed field name. */
    public const EMBED_FIELD_NAME_MAX = 256;

    /** Maximum characters in an embed field value. */
    public const EMBED_FIELD_VALUE_MAX = 1024;

    /** Maximum number of fields in an embed. */
    public const EMBED_FIELDS_MAX = 25;

    /** Maximum combined characters across all embed text fields. */
    public const EMBED_TOTAL_CHARS_MAX = 6000;

    /** Maximum characters in a message content field. */
    public const MESSAGE_CONTENT_MAX = 2000;

    /** Maximum embeds attached to a single message. */
    public const MESSAGE_EMBEDS_MAX = 10;

    /** Maximum file attachments on a single message. */
    public const MESSAGE_FILES_MAX = 10;
}
