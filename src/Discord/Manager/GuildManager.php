<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

/**
 * This file is part of DiscordPHP.
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
