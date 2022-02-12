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
 * Application Command Permissions Class.
 *
 * @see https://discord.com/developers/docs/interactions/application-commands#application-command-permissions-object-application-command-permissions-structure
 *
 * @property string $id         The id of the role or user
 * @property int    $type       Role or user
 * @property bool   $permission True to allow, false, to disallow
 */
class Permission extends Part
{
    public const TYPE_ROLE = 1;
    public const TYPE_USER = 2;

    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'type', 'permission'];
}
