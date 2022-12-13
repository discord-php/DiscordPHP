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

/**
 * Represents the header attached to an Opus Ogg file.
 *
 * @link https://www.rfc-editor.org/rfc/rfc7845#section-5.1 Identification Header
 *
 * @since 10.0.0
 *
 * @internal
 */
class OpusHead
{
    /**
     * Binary format string used to parse header.
     *
     * @var string
     */
    private const FORMAT = 'Cversion/Cchannel_count/vpre_skip/Vsample_rate/voutput_gain/Cchannel_map_family';

    /**
     * Binary format string used to parse optional header.
     *
     * @var string
     */
    private const STREAM_COUNT_FORMAT = 'Cstream_count/Ctwo_channel_stream_count';

    /**
     * Version number of Opus file. Should always be 1.
     *
     * @var int
     */
    public int $version;

    /**
     * Number of channels in the Opus file.
     *
     * @var int
     */
    public int $channelCount;

    /**
     * The number of samples (at 48 kHz) to discard from the decoder output when
     * starting playback.
     *
     * @var int
     */
    public int $preSkip;

    /**
     * The sample rate of the original input (before encoding), in Hz. This
     * field is _not_ the sample rate to use for playback of the encoded data.
     *
     * @var int
     */
    public int $sampleRate;

    /**
     * The gain to be applied when decoding.
     *
     * @var int
     */
    public int $outputGain;

    /**
     * Indicates the order and semantic meaning of the output channels.
     *
     * @var int
     */
    public int $channelMapFamily;

    /**
     * The total number of streams encoded in each Ogg packet.
     *
     * @var null|int
     */
    public ?int $streamCount = null;

    /**
     * The number of streams whose decoders are to be configured to produce two
     * channels (stereo).
     *
     * @var null|int
     */
    public ?int $twoChannelStreamCount = null;

    /**
     * Contains one octet per output channel, indicating which decoded channel
     * is to be used for each one.
     *
     * @var int[]|null
     */
    public ?array $cmap = null;

    /**
     * Create an instance of OpusHead from a binary string.
     *
     * @param string $data Binary string of data.
     *
     * @throws \UnexpectedValueException If the binary data was missing the magic bytes.
     */
    public function __construct(string $data)
    {
        $magic = substr($data, 0, 8);
        if ($magic != 'OpusHead') {
            throw new \UnexpectedValueException("Expected OpusHead, found {$magic}.");
        }

        $head = unpack(self::FORMAT, $data, 8);
        $this->version = $head['version'];
        $this->channelCount = $head['channel_count'];
        $this->preSkip = $head['pre_skip'];
        $this->sampleRate = $head['sample_rate'];
        $this->outputGain = $head['output_gain'];
        $this->channelMapFamily = $head['channel_map_family'];

        if ($head['channel_map_family'] > 0) {
            $stream_counts = unpack(self::STREAM_COUNT_FORMAT, $data, 19);
            $this->streamCount = $stream_counts['stream_count'];
            $this->twoChannelStreamCount = $stream_counts['two_channel_stream_count'];
            $this->cmap = array_values(unpack('C*', $data, 21));
        }
    }
}
