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

use Exception;
use Discord\Helpers\Buffer;
use React\Promise\ExtendedPromiseInterface;

class OggPage
{
    private const FORMAT = 'Cversion/Cheader_type/Pgranule_position/Vbitstream_sn/Vpage_seq/Vcsum/Cpage_segments';

    private function __construct(
        private int $version,
        private int $headerType,
        private int $granulePosition,
        private int $bitstreamSn,
        private int $pageSeq,
        private int $checksum,
        private array $pageSegments,
        public string $segmentData,
    ) {
    }

    /**
     * Read an Ogg page from a buffer.
     *
     * @param Buffer $buffer Buffer to read the Ogg page from.
     *
     * @return ExtendedPromiseInterface Promise containing the Ogg page.
     *
     * @throws Exception If the buffer is out of sync and an invalid header is read.
     */
    public static function fromBuffer(Buffer $buffer): ExtendedPromiseInterface
    {
        $header = null;
        $pageSegments = [];

        return $buffer->read(4)->then(function ($magic) use ($buffer) {
            if ($magic !== 'OggS') {
                throw new Exception('Invalid Ogg page header, expected OggS got '.$magic);
            }

            return $buffer->read(23);
        })
            // Reading header
            ->then(function ($data) use ($buffer, &$header) {
                $header = unpack(Self::FORMAT, $data);

                return $buffer->read($header['page_segments']);
            })
            // Reading page segment lengths
            ->then(function ($data) use ($buffer, &$pageSegments) {
                $pageSegments = unpack('C*', $data);
                $data = array_sum($pageSegments);

                return $buffer->read($data);
            })
            // Reading segment data
            ->then(function ($data) use (&$header, &$pageSegments) {
                return new OggPage(
                    $header['version'],
                    $header['header_type'],
                    $header['granule_position'],
                    $header['bitstream_sn'],
                    $header['page_seq'],
                    $header['csum'],
                    $pageSegments,
                    $data
                );
            });
    }

    public function iterPackets()
    {
        $packetLen = 0;
        $offset = 0;
        $partial = true;

        foreach ($this->pageSegments as $seg) {
            $packetLen += $seg;
            if ($seg == 255) {
                $partial = true;
            } else {
                yield [substr($this->segmentData, $offset, $packetLen), true];
                $offset += $packetLen;
                $packetLen = 0;
                $partial = false;
            }
        }

        if ($partial) {
            yield [substr($this->segmentData, $offset), false];
        }
    }
}
