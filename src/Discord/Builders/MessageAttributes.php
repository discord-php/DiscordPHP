<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders;

/**
 * Common Message attributes.
 *
 * @see Discord\Parts\Channel\Message
 * @see Discord\Builders\AbstractMessageBuilder
 * @see Discord\Builders\MessageBuilder
 * @see Discord\Builders\WebhookMessageBuilder
 *
 * @property string|null $content The content of the message if it is a normal message.
 * @property int|null    $flags   Message flags.
 */
trait MessageAttributes
{
}
