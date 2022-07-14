<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Discord\Helpers\Collection;
use Discord\Parts\Part;

/**
 * A Welcome Screen of a Guild.
 *
 * @see https://discord.com/developers/docs/resources/guild#welcome-screen-object-welcome-screen-structure
 *
 * @property string                      $description      The server description shown in the welcome screen.
 * @property Collection|WelcomeChannel[] $welcome_channels The channels shown in the welcome screen, up to 5.
 */
class WelcomeScreen extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['description', 'welcome_channels'];

    /**
     * Returns the Welcome Channels of the Welcome Screen.
     *
     * @return Collection|WelcomeChannel[] The channels of welcome screen.
     */
    protected function getWelcomeChannelsAttribute(): Collection
    {
        $collection = Collection::for(WelcomeChannel::class, null);

        foreach ($this->attributes['welcome_channels'] ?? [] as $welcome_channel) {
            $collection->pushItem($this->factory->part(WelcomeChannel::class, (array) $welcome_channel, true));
        }

        return $collection;
    }
}
