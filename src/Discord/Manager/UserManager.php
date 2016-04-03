<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Manager;

use Discord\Model\AbstractModel;
use Discord\Model\User;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class UserManager extends AbstractManager
{
    /**
     * @return string
     */
    public function getModel()
    {
        return User::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdFromData(array $data)
    {
        return $data['user']['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function create($data, $complete = true)
    {
        if (isset($data['id'])) {
            if (!isset($data['user'])) {
                $data['user'] = [];
            }
            $data['user']['id'] = $data['id'];

            unset($data['id']);
        }

        return parent::create($data, $complete);
    }
}
