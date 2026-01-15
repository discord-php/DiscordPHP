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

namespace Discord\Parts\Guild;

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * A Welcome Screen of a Guild.
 *
 * @link https://discord.com/developers/docs/resources/guild#welcome-screen-object-welcome-screen-structure
 *
 * @since 7.0.0
 *
 * @property ?string                                                $description      The server description shown in the welcome screen.
 * @property ExCollectionInterface<WelcomeChannel>|WelcomeChannel[] $welcome_channels The channels shown in the welcome screen, up to 5.
 */
class WelcomeScreen extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'description',
        'welcome_channels',
    ];

    /**
     * Returns the Welcome Channels of the Welcome Screen.
     *
     * @return ExCollectionInterface<WelcomeChannel>|WelcomeChannel[] The channels of welcome screen.
     */
    protected function getWelcomeChannelsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('welcome_channels', WelcomeChannel::class, 'channel_id');
    }
}
