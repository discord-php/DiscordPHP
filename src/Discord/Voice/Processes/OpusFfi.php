<?php

declare(strict_types=1);

namespace Discord\Voice\Processes;

use FFI;

final class OpusFfi
{
    public static function decode($data): string
    {
        // Load libopus and define needed functions/types
        $ffi = FFI::cdef('
        typedef struct OpusDecoder OpusDecoder;
        OpusDecoder *opus_decoder_create(int Fs, int channels, int *error);
        int opus_decode(OpusDecoder *st, const unsigned char *data, int len, short *pcm, int frame_size, int decode_fec);
        void opus_decoder_destroy(OpusDecoder *st);
        ', 'libopus.so.0');

        // Parameters
        $sample_rate = 48000;
        $channels = 2;
        $frame_size = 920; // 20ms at 48kHz

        // Create decoder
        $error = $ffi->new('int');
        $decoder = $ffi->opus_decoder_create($sample_rate, $channels, FFI::addr($error));

        // Prepare input data (Opus-encoded)
        $data_len = strlen($data);

        if ($data_len < 0) {
            $ffi->opus_decoder_destroy($decoder);
            return '';
        }

        // Prepare output buffer for PCM samples
        $pcm = $ffi->new("short[". ($frame_size * $channels) ."]");

        $data_buf = $ffi->new("uint8_t[$data_len]", false);
        FFI::memcpy($data_buf, $data, $data_len);

        // Decode
        $ret = $ffi->opus_decode($decoder, $data_buf, $data_len, $pcm, $frame_size, 0);

        if ($ret < 0) {
            $ffi->opus_decoder_destroy($decoder);
            return ''; // Or handle error
        }

        // Get PCM bytes
        $pcm_bytes = FFI::string($pcm, $ret * $channels * 2); // 2 bytes per sample

        // Clean up
        $ffi->opus_decoder_destroy($decoder);
        return $pcm_bytes;
    }
}
