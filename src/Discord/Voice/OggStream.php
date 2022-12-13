<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Voice;

use Discord\Exceptions\BufferTimedOutException;
use Discord\Helpers\Buffer;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;

use function React\Promise\resolve;

/**
 * Represents an Ogg Opus stream.
 *
 * @link https://www.rfc-editor.org/rfc/rfc7845
 *
 * @since 10.0.0
 *
 * @internal
 */
class OggStream
{
    /**
     * Leftover bytes from the previous Ogg packet.
     *
     * @var string
     */
    private string $leftover = '';

    /**
     * Buffer of packets that have been parsed and split into Opus chunks.
     *
     * @var string[]|null
     */
    private ?array $packets = [];

    /**
     * Create a new Ogg Opus stream.
     *
     * @param Buffer   $buffer Buffer to read Ogg Opus packets from.
     * @param OpusHead $header The header that has already been read from `$buffer`.
     * @param OpusTags $tags   The tags that have already been read from `$buffer`.
     */
    private function __construct(
        private Buffer $buffer,
        public OpusHead $header,
        public OpusTags $tags
    ) {
    }

    /**
     * Create a new Ogg Opus stream from a buffer. This will read the Opus
     * header and the Opus tags and return a new Ogg stream ready to read Opus
     * packets.
     *
     * @param Buffer $buffer  Buffer to read Ogg Opus packets from.
     * @param ?int   $timeout Time in milliseconds before a buffer read times out.
     *
     * @return ExtendedPromiseInterface<OggStream> A promise containing the Ogg stream.
     */
    public static function fromBuffer(Buffer $buffer, ?int $timeout = -1): ExtendedPromiseInterface
    {
        /** @var OpusHead */
        $header = null;

        return OggPage::fromBuffer($buffer, $timeout)->then(function (OggPage $page) use (&$header, $buffer, $timeout) {
            $header = new OpusHead($page->segmentData);

            return OggPage::fromBuffer($buffer, $timeout);
        })->then(function (OggPage $page) use (&$header, $buffer) {
            $tags = new OpusTags($page->segmentData);

            return new OggStream($buffer, $header, $tags);
        });
    }

    /**
     * Attempt to get a packet from the Ogg stream.
     *
     * @return ExtendedPromiseInterface<string|null> Promise containing an Opus packet. If null, indicates EOF.
     */
    public function getPacket(): ExtendedPromiseInterface
    {
        if ($this->packets === null) {
            return resolve(null);
        } elseif (count($this->packets) > 0) {
            return resolve(array_shift($this->packets));
        }

        return $this->parsePackets()->then(function ($packets) {
            if ($packets === null) {
                $this->packets = null;

                return null;
            }

            $this->packets = array_merge($this->packets, $packets);

            return $this->getPacket();
        });
    }

    /**
     * Attempt to read an Ogg page from the buffer and parse it into Opus
     * packets.
     *
     * @return ExtendedPromiseInterface<string[]|null> Promise containing an array of Opus packets.
     */
    private function parsePackets(): ExtendedPromiseInterface
    {
        return new Promise(function ($resolve, $reject) {
            OggPage::fromBuffer($this->buffer, timeout: 0)->then(function ($page) use ($resolve) {
                $packets = [];
                $partial = $this->leftover;
                foreach ($page->iterPackets() as [$data, $complete]) {
                    $partial .= $data;
                    if ($complete) {
                        $packets[] = $partial;
                        $partial = '';
                    }
                }
                $this->leftover = $partial;

                $resolve($packets);
            }, function (\Exception $e) use ($resolve, $reject) {
                if ($e instanceof BufferTimedOutException) {
                    $resolve(null);
                } else {
                    $reject($e);
                }
            });
        });
    }
}
