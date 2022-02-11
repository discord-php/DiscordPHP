<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Interaction;

use Discord\Parts\Interactions\Request\Component;
use Discord\Repository\AbstractRepository;

class ComponentRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $class = Component::class;

    /**
     * @inheritdoc
     */
    protected $discrim = null;
}
