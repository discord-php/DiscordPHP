<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Discord\Parts\Part;

/**
 * The colors of a role.
 *
 * This object will always be filled with primary_color being the role's color.
 * Other fields can only be set to a non-null value if the guild has the ENHANCED_ROLE_COLORS guild feature.
 *
 * When sending tertiary_color the API enforces the role color to be a holographic style with values of:
 * primary_color = 11127295, secondary_color = 16759788, and tertiary_color = 16761760.
 *
 * @link https://discord.com/developers/docs/topics/permissions#role-object-role-colors-object
 *
 * @since 10.18.1
 *
 * @property int      $primary_color   The primary color for the role.
 * @property int|null $secondary_color The secondary color for the role, this will make the role a gradient between the other provided colors.
 * @property int|null $tertiary_color  The tertiary color for the role, this will turn the gradient into a holographic style.
 */
class Colors extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'primary_color',
        'secondary_color',
        'tertiary_color',
    ];
}
