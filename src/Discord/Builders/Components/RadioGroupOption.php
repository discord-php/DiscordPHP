<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders\Components;

/**
 * List of options to render within a Radio Group.
 *
 * @link https://discord.com/developers/docs/components/reference#radio-group-option-structure
 *
 * @since 10.46.0
 *
 * @property string       $value       Dev-defined value of the option; max 100 characters.
 * @property string       $label       User-facing label of the option; max 100 characters.
 * @property ?string|null $description Optional description for the option; max 100 characters.
 * @property ?bool|null   $default     Shows the option as selected by default.
 */
class RadioGroupOption extends GroupOption
{
}
