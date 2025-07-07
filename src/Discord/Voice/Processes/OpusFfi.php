<?php

declare(strict_types=1);

namespace Discord\Voice\Processes;

use FFI;

/**
 * Handles the decoding of Opus audio data using FFI (Foreign Function Interface).
 *
 * @since 10.19.0
 */
final class OpusFfi
{
    /**
     * Creates a FFI instance (code in C) to decode Opus audio data.
     * By using the libopus library, this function decodes Opus-encoded audio data
     * into PCM samples.
     * This is useful for processing audio data in Discord voice channels.
     * @param string|mixed $data
     *
     * @return string Returns the decoded PCM audio data as a string/binary.
     */
    public static function decode($data): string
    {
        // Load libopus and define needed functions/types
        // TODO: Move this to a separate file or class if needed.
        $ffi = FFI::cdef('
        typedef struct OpusDecoder OpusDecoder;
        typedef short opus_int16;
        typedef int opus_int32;

        int opus_packet_get_nb_frames(const unsigned char packet[], opus_int32 len);
        int opus_packet_get_samples_per_frame(const unsigned char * data, opus_int32 Fs);

        OpusDecoder *opus_decoder_create(opus_int32 Fs, int channels, int *error);
        int opus_decode(OpusDecoder *st, const unsigned char *data, opus_int32 len, opus_int16 *pcm, int frame_size, int decode_fec);
        void opus_decoder_destroy(OpusDecoder *st);
        ', 'libopus.so.0');

        // Parameters
        $sampleRate = 48000;
        $channels = 2;

        $dataLength = strlen($data);

        $dataBuffer = $ffi->new("const unsigned char[$dataLength]", false);
        FFI::memcpy($dataBuffer, $data, $dataLength);

        $frames = $ffi->opus_packet_get_nb_frames($dataBuffer, $dataLength);
        $samplesPerFrame = $ffi->opus_packet_get_samples_per_frame($dataBuffer, $sampleRate);
        $frameSize = $frames * $samplesPerFrame;

        // Create decoder
        $error = $ffi->new('int');
        $decoder = $ffi->opus_decoder_create($sampleRate, $channels, FFI::addr($error));

        // Prepare input data (Opus-encoded)

        if ($dataLength < 0) {
            $ffi->opus_decoder_destroy($decoder);
            return '';
        }

        // Prepare output buffer for PCM samples
        $pcm = $ffi->new("opus_int16[" . $frameSize * $channels * 2 . "]", false);

        // Decode
        $ret = $ffi->opus_decode($decoder, $dataBuffer, $dataLength, $pcm, $frameSize, 0);

        if ($ret < 0) {
            $ffi->opus_decoder_destroy($decoder);
            // TODO: Handle decoding error
            return ''; // Or handle error
        }

        // Get PCM bytes
        $pcm_bytes = FFI::string($pcm, $ret * $channels * 2); // 2 bytes per sample

        // Clean up
        $ffi->opus_decoder_destroy($decoder);

        return $pcm_bytes;
    }
}
