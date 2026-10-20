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

namespace Discord\RPC;

/** A connection CLOSE code, separate from command error codes. */
final class CloseException extends \RuntimeException
{
    public function __construct(int $closeCode, private string $reason)
    {
        parent::__construct('RPC connection closed (code '.$closeCode.').', $closeCode);
    }

    /** Remote diagnostic text; callers should not log it without redaction. */
    public function getReason(): string
    {
        return $this->reason;
    }
}
