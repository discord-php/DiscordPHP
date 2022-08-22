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

use Discord\Parts\Interactions\Request\Option;
use Discord\Repository\AbstractRepository;

/**
 * Contains options for application commands.
 *
 * @see Option
 * @see \Discord\Parts\Interactions\Command\Command
 *
 * @method Option|null get(string $discrim, $key)
 * @method Option|null pull(string|int $key, $default = null)
 * @method Option|null first()
 * @method Option|null last()
 * @method Option|null find()
 */
class OptionRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $class = Option::class;

    /**
     * @inheritdoc
     */
    protected $discrim = 'name';
}
