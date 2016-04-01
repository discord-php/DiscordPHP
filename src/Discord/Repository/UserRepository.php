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
namespace Discord\Repository;

use Discord\Parts\User\User;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class UserRepository extends AbstractRepository
{
    public function findOneById($id)
    {
        $key = 'users.'.$id;
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $data = $this->http->get('users/'.$id);
        $user = $this->partFactory->create(User::class, $data, true);

        return $this->cache->set($key, $user);
    }
}
