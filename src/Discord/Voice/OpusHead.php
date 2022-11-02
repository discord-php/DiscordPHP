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

class OpusHead
{
    private const FORMAT = 'Cversion/Cchannel_count/vpre_skip/Vsample_rate/voutput_gain/Cchannel_map_family';
    private const STREAM_COUNT_FORMAT = 'Cstream_count/Ctwo_channel_stream_count';

    public int $version;
    public int $channelCount;
    public int $preSkip;
    public int $sampleRate;
    public int $outputGain;
    public int $channelMapFamily;
    public ?int $streamCount = null;
    public ?int $twoChannelStreamCount = null;
    public ?array $cmap = null;

    public function __construct(string $data)
    {
        $magic = substr($data, 0, 8);
        if ($magic != 'OpusHead') {
            throw new Exception("Expected OpusHead, found $magic");
        }

        $head = unpack(Self::FORMAT, $data, 8);
        $this->version = $head['version'];
        $this->channelCount = $head['channel_count'];
        $this->preSkip = $head['pre_skip'];
        $this->sampleRate = $head['sample_rate'];
        $this->outputGain = $head['output_gain'];
        $this->channelMapFamily = $head['channel_map_family'];

        if ($head['channel_map_family'] > 0) {
            $stream_counts = unpack(Self::STREAM_COUNT_FORMAT, $data, 19);
            $this->streamCount = $stream_counts['stream_count'];
            $this->twoChannelStreamCount = $stream_counts['two_channel_stream_count'];
            $this->cmap = array_values(unpack('C*', $data, 21));
        }
    }
}
