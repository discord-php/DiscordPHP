<?php

declare(strict_types=1);

namespace Discord\Voice\Client;

use Discord\Discord;
use Discord\Parts\EventData\VoiceSpeaking;
use Discord\Voice\ReceiveStream;
use Discord\Voice\VoiceClient;
use React\ChildProcess\Process;

/**
 * @since 10.19.0
 */
final class User
{
    public function __construct(
        protected Discord $discord,
        protected VoiceClient $voiceClient,
        protected int $ssrc,
        protected Process $decoder,
        protected ReceiveStream $stream,
        protected ?VoiceSpeaking $part = null,
    ) {
    }
}
