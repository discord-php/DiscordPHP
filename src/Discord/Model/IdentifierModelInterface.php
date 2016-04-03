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
interface IdentifierModelInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @param string $id
     *
     * @return IdentifierModelInterface
     */
    public function setId($id);
}
