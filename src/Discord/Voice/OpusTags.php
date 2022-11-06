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
 * Represents Vorbis tags attached to an Opus Ogg file.
 *
 * @link https://www.rfc-editor.org/rfc/rfc7845#section-5.2 Comment Header
 *
 * @since 10.0.0
 *
 * @internal
 */
class OpusTags
{
    /**
     * The vendor of the Opus Ogg.
     *
     * @var string
     */
    public string $vendor;

    /**
     * An array of tags attached to the Opus Ogg.
     *
     * @var string[]
     */
    public array $tags;

    /**
     * Create an instance of OpusTags from a binary string.
     *
     * @param string $data Binary string of data.
     *
     * @throws \UnexpectedValueException If the binary data was missing the magic bytes.
     */
    public function __construct(string $data)
    {
        $magic = substr($data, 0, 8);
        if ($magic != 'OpusTags') {
            throw new \UnexpectedValueException("Expected OpusTags, found {$magic}.");
        }

        $vendor_len = unpack('Vvendor_len', $data, 8)['vendor_len'];
        $this->vendor = substr($data, 12, $vendor_len);

        $tags = [];
        $num_tags = unpack('Vnum_tags', $data, 12 + $vendor_len)['num_tags'];
        $data = substr($data, 16 + $vendor_len);
        for ($i = 0; $i < $num_tags; $i++) {
            $tag_len = unpack('Vtag_len', $data)['tag_len'];
            $tags[$i] = substr($data, 4, $tag_len);
            $data = substr($data, 4 + $tag_len);
        }
        $this->tags = $tags;
    }
}
