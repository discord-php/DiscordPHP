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

namespace Discord\Parts\Channel\Message\Embed;

use Discord\Discord;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Message\Container;

/**
 * A display-only component link preview received from Discord.
 *
 * Author website previews with ComponentEmbedBuilder; bots cannot send this embed type.
 *
 * @link https://docs.discord.com/developers/link-previews/component-embeds
 *
 * @property-read ExCollectionInterface<Container>|Container[] $components The single received Container, or an empty collection for an absent/null field.
 *
 * @phpstan-property ExCollectionInterface<Container> $components
 */
class EmbedComponents extends Embed
{
    public const TYPE = self::TYPE_COMPONENTS;

    /** @inheritDoc */
    public function __construct(Discord $discord, array $attributes = [], bool $created = false)
    {
        $this->fillable[] = 'components';
        parent::__construct($discord, $attributes, $created);
    }

    /**
     * Returns the Container received in a component embed.
     *
     * @return ExCollectionInterface<Container>|Container[]
     */
    protected function getComponentsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('components', Container::class);
    }
}
