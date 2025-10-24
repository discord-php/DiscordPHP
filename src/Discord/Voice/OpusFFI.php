<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Voice\Processes;

use FFI;

/**
 * Handles the decoding of Opus audio data using FFI (Foreign Function Interface).
 *
 * @todo
 *
 * @property FFI   $ffi
 * @method   int   opus_packet_get_nb_frames(mixed $packet, int $len)
 * @method   int   opus_packet_get_samples_per_frame(mixed $data, int $Fs)
 * @method   mixed opus_decoder_create(int $Fs, int $channels, mixed $error)
 * @method   int   opus_decode(mixed $st, mixed $data, int $len, mixed $pcm, int $frame_size, int $decode_fec)
 * @method   void  opus_decoder_destroy(mixed $st)
 */
class OpusFFI
{
    protected FFI $ffi;

    public function __construct()
    {
        // Load libopus and define needed functions/types
        $this->ffi = FFI::cdef('
        typedef struct OpusDecoder OpusDecoder;
        typedef short opus_int16;
        typedef int opus_int32;

        int opus_packet_get_nb_frames(const unsigned char packet[], opus_int32 len);
        int opus_packet_get_samples_per_frame(const unsigned char * data, opus_int32 Fs);

        OpusDecoder *opus_decoder_create(opus_int32 Fs, int channels, int *error);
        int opus_decode(OpusDecoder *st, const unsigned char *data, opus_int32 len, opus_int16 *pcm, int frame_size, int decode_fec);
        void opus_decoder_destroy(OpusDecoder *st);
        ', 'libopus.so.0');
    }

    /**
     * Creates a FFI instance (code in C) to decode Opus audio data.
     * By using the libopus library, this function decodes Opus-encoded audio data
     * into PCM samples.
     *
     * @param string|mixed $data The Opus-encoded audio data to decode.
     *
     * @return string The decoded PCM audio data as a string/binary.
     */
    public function decode($data, int $channels = 2, int $audioRate = 48000): string
    {
        $dataLength = strlen($data);
        if ($dataLength < 0) {
            return '';
        }

        $dataBuffer = $this->ffi->new("const unsigned char[$dataLength]", false);
        FFI::memcpy($dataBuffer, $data, $dataLength);

        $frames = $this->opus_packet_get_nb_frames($dataBuffer, $dataLength);
        $samplesPerFrame = $this->opus_packet_get_samples_per_frame($dataBuffer, $audioRate);
        $frameSize = $frames * $samplesPerFrame;

        // Create decoder
        $error = $this->ffi->new('int');
        $decoder = $this->opus_decoder_create($audioRate, $channels, FFI::addr($error));

        // Prepare output buffer for PCM samples
        $pcm = $this->ffi->new('opus_int16['.$frameSize * $channels * 2 .']', false);

        // Decode
        $ret = $this->opus_decode($decoder, $dataBuffer, $dataLength, $pcm, $frameSize, 0);

        // Clean up
        $this->opus_decoder_destroy($decoder);

        if ($ret < 0) {
            /** @todo Handle decoding error */
            return '';
        }

        // 2 bytes per sample
        return FFI::string($pcm, $ret * $channels * 2);
    }

    /**
     * Magic method to redirect method calls to the FFI instance.
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->ffi->$name(...$arguments);
    }
}
