<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Command;

use Discord\Parts\Part;

/**
 * Application Command Permissions Overwrite Class
 *
 * @property string $id         the id of the role or user
 * @property int    $type       role or user
 * @property bool   $permission true to allow, false, to disallow
 */
class Overwrite extends Part
{
    public const ROLE = 1;
    public const USER = 2;

    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'type', 'permission'];
}
