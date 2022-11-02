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

use Discord\Helpers\Buffer;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\resolve;

class OggStream
{
    private string $leftover = '';
    private array $packets = [];

    private function __construct(
        private Buffer $buffer,
        public OpusHead $header,
        public OpusTags $tags
    ) {
    }

    public static function fromBuffer(Buffer $buffer)
    {
        /** @var OpusHead */
        $header = null;

        return OggPage::fromBuffer($buffer)->then(function (OggPage $page) use (&$header, $buffer) {
            $header = new OpusHead($page->segmentData);

            return OggPage::fromBuffer($buffer);
        })->then(function (OggPage $page) use (&$header, $buffer) {
            $tags = new OpusTags($page->segmentData);

            return new OggStream($buffer, $header, $tags);
        });
    }

    public function getPacket(): ExtendedPromiseInterface
    {
        if (count($this->packets) > 0) {
            return resolve(array_shift($this->packets));
        }

        return $this->parsePackets()->then(function ($packets) {
            $this->packets = array_merge($this->packets, $packets);

            return $this->getPacket();
        });
    }

    public function parsePackets(): ExtendedPromiseInterface
    {
        return OggPage::fromBuffer($this->buffer)->then(function ($page) {
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

            return $packets;
        });
    }
}
