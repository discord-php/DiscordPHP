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

class OpusTags
{
    public string $vendor;
    public array $tags = [];

    public function __construct(string $data)
    {
        $magic = substr($data, 0, 8);
        if ($magic != 'OpusTags') {
            throw new Exception("Expected OpusTags, found $magic");
        }

        $vendor_len = unpack('Vvendor_len', $data, 8)['vendor_len'];
        $this->vendor = substr($data, 12, $vendor_len);

        $num_tags = unpack('Vnum_tags', $data, 12 + $vendor_len)['num_tags'];
        $data = substr($data, 16 + $vendor_len);
        for ($i = 0; $i < $num_tags; $i++) {
            echo "$i";
            $tag_len = unpack('Vtag_len', $data)['tag_len'];
            $this->tags[$i] = substr($data, 4, $tag_len);
            $data = substr($data, 4 + $tag_len);
        }
    }
}
