<?php

/**
 * This file is part of DiscordPHP
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Manager;

use Discord\Model\Guild;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class GuildManager extends AbstractManager
{
    /**
     * @return string
     */
    public function getModel()
    {
        return Guild::class;
    }
}
