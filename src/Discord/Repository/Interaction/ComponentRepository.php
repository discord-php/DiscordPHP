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

/**
 * Contains components for message interaction.
 *
 * @see Component
 * @see \Discord\Parts\Interactions\Interaction
 *
 * @since 7.0.0
 *
 * @method Component|null get(string $discrim, $key)
 * @method Component|null pull(string|int $key, $default = null)
 * @method Component|null first()
 * @method Component|null last()
 * @method Component|null find()
 */
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
