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

use Discord\Http\Endpoint;
use Discord\Http\Http;
use Discord\Parts\Part;
use React\Promise\ExtendedPromiseInterface;

/**
 * A Widget of a Guild.
 *
 * @see https://discord.com/developers/docs/resources/guild#get-guild-widget-object
 *
 * @property string      $id             Guild id.
 * @property Guild|null  $guild          Guild.
 * @property string      $name           Guild name (2-100 characters).
 * @property string|null $instant_invite Instant invite for the guilds specified widget invite channel.
 * @property object[]    $channels       Voice and stage channels which are accessible by @everyone.
 * @property object[]    $members        Special widget user objects that includes users presence (Limit 100).
 * @property int         $presence_count Number of online members in this guild.
 * @property string      $image
 */
class Widget extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
        'name',
        'instant_invite',
        'channels',
        'members',
        'presence_count',
    ];

    public const STYLE = [
        'shield',
        'banner1',
        'banner2',
        'banner3',
        'banner4',
    ];

    /**
     * @inheritdoc
     */
    public function fetch(): ExtendedPromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::GUILD_WIDGET, $this->id))
            ->then(function ($response) {
                $this->fill((array) $response);

                if ($this->guild) {
                    $this->guild->widget = $this;
                }

                return $this;
            });
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->id);
    }

    /**
     * Returns a PNG image widget for the guild. Requires no permissions or authentication.
     *
     * @param string|null $style Style of the widget image returned
     *
     * @return string
     */
    public function getImageAttribute(?string $style = 'shield'): string
    {
        $endpoint = Endpoint::bind(Endpoint::GUILD_WIDGET_IMAGE, $this->id);

        if (in_array(strtolower($style), self::STYLE)) {
            $endpoint->addQuery('style', $style);
        }

        return Http::BASE_URL.'/'.$endpoint;
    }
}
