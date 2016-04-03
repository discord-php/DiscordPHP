<?php

/**
 * This file is part of DiscordPHP
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Model;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class AbstractModel
{
    /**
     * @var bool
     */
    protected $built = false;

    /**
     * @return boolean
     */
    public function isBuilt()
    {
        return $this->built;
    }

    /**
     * @param boolean $built
     *
     * @return AbstractModel
     */
    public function setBuilt($built)
    {
        $this->built = $built;

        return $this;
    }
}
