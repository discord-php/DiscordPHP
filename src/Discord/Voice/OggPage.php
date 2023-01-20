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
use Generator;
use React\Promise\ExtendedPromiseInterface;

/**
 * Represents a page in an Ogg container.
 *
 * @link https://www.rfc-editor.org/rfc/rfc3533
 *
 * @since 10.0.0
 *
 * @internal
 */
class OggPage
{
    /**
     * Binary format string used to parse header.
     *
     * @var string
     */
    private const FORMAT = 'Cversion/Cheader_type/Pgranule_position/Vbitstream_sn/Vpage_seq/Vcsum/Cpage_segments';

    /**
     * Create a new Ogg page.
     *
     * @param int    $version         The version number of the Ogg file format used in this stream.
     * @param int    $headerType      Identifies the specific type of this page.
     * @param int    $granulePosition Contains position information.
     * @param int    $bitstreamSn     Contains the unique serial number by which the logical bitstream is identified.
     * @param int    $pageSeq         Contains the sequence number of the page so the decoder can identify page loss.
     * @param int    $checksum        Contains a 32 bit CRC checksum of the page (including header with zero CRC field and page content).
     * @param int[]  $pageSegments    A list of page segment lengths which were contained within the page.
     * @param string $segmentData     The data of all the page segments concatenated.
     *
     * @link https://www.rfc-editor.org/rfc/rfc3533#section-6 The Ogg page format
     */
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
     * @param Buffer $buffer  Buffer to read the Ogg page from.
     * @param ?int   $timeout Time in milliseconds before a buffer read times out.
     *
     * @return ExtendedPromiseInterface<OggPage> Promise containing the Ogg page.
     *
     * @throws \UnexpectedValueException If the buffer is out of sync and an invalid header is read.
     */
    public static function fromBuffer(Buffer $buffer, ?int $timeout = -1): ExtendedPromiseInterface
    {
        $header = null;
        $pageSegments = [];

        return $buffer->read(4, timeout: $timeout)->then(function ($magic) use ($buffer, $timeout) {
            if ($magic !== 'OggS') {
                throw new \UnexpectedValueException("Invalid Ogg page header, expected OggS got {$magic}.");
            }

            return $buffer->read(23, timeout: $timeout);
        })
            // Reading header
            ->then(function ($data) use ($buffer, &$header, $timeout) {
                $header = unpack(self::FORMAT, $data);

                return $buffer->read($header['page_segments'], timeout: $timeout);
            })
            // Reading page segment lengths
            ->then(function ($data) use ($buffer, &$pageSegments, $timeout) {
                $pageSegments = unpack('C*', $data);
                $data = array_sum($pageSegments);

                return $buffer->read($data, timeout: $timeout);
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

    /**
     * Iterates through the packets contained within the stream, yielding an
     * array containing binary data and whether the packet is complete, from a
     * generator.
     *
     * @return Generator
     */
    public function iterPackets()
    {
        $packetLen = 0;
        $offset = 0;
        $partial = true;

        foreach ($this->pageSegments as $seg) {
            $packetLen += $seg;
            if ($seg == 255) {
                $partial = true;
                continue;
            }

            yield [substr($this->segmentData, $offset, $packetLen), true];
            $offset += $packetLen;
            $packetLen = 0;
            $partial = false;
        }

        if ($partial) {
            yield [substr($this->segmentData, $offset), false];
        }
    }
}
