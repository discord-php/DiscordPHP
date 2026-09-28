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
use Discord\Http\Endpoint;
use Discord\Http\Http;
use Discord\Http\Request;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * A Widget of a Guild.
 *
 * @link https://docs.discord.com/developers/resources/guild#guild-widget-object
 *
 * @since 7.0.0
 *
 * @property      string                                   $id             Guild id.
 * @property-read Guild|null                               $guild          Guild.
 * @property      string                                   $name           Guild name (2-100 characters).
 * @property      ?string                                  $instant_invite Instant invite for the guilds specified widget invite channel.
 * @property      ExCollectionInterface<Channel>|Channel[] $channels       Voice and stage channels which are accessible by @everyone.
 * @property      ExCollectionInterface<Member>|Member[]   $members        Special widget user objects that includes users presence (Limit 100).
 * @property      int                                      $presence_count Number of online members in this guild.
 *
 * @property-read string $image
 *
 * @phpstan-property ExCollectionInterface<Channel> $channels
 * @phpstan-property ExCollectionInterface<Member>  $members
 */
class Widget extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'name',
        'instant_invite',
        'channels',
        'members',
        'presence_count',
    ];

    /** Shield style widget with Discord icon and guild members online count. */
    public const STYLE_SHIELD = 'shield';

    /** Large image with guild icon, name and online count. "POWERED BY DISCORD" as the footer of the widget. */
    public const STYLE_BANNER1 = 'banner1';

    /** * Smaller widget style with guild icon, name and online count. Split on the right with Discord logo. */
    public const STYLE_BANNER2 = 'banner2';

    /** Large image with guild icon, name and online count. In the footer, Discord logo on the left and "Chat Now" on the right. */
    public const STYLE_BANNER3 = 'banner3';

    /**
     * Large Discord logo at the top of the widget. Guild icon, name and online
     * count in the middle portion of the widget and a "JOIN MY SERVER" button
     * at the bottom.
     */
    public const STYLE_BANNER4 = 'banner4';

    public const STYLE = [
        self::STYLE_SHIELD,
        self::STYLE_BANNER1,
        self::STYLE_BANNER2,
        self::STYLE_BANNER3,
        self::STYLE_BANNER4,
    ];

    /**
     * @inheritDoc
     */
    public function fetch(): PromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::GUILD_WIDGET, $this->id))
            ->then(function ($response) {
                $this->fill((array) $response);
                $this->created = true;

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
     * Returns the channels attribute.
     *
     * @return ExCollectionInterface<Channel>|Channel[] A collection of channels.
     */
    protected function getChannelsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('channels', Channel::class);
    }

    /**
     * Returns the members attribute.
     *
     * @return ExCollectionInterface<Member>|Member[] A collection of members.
     */
    protected function getMembersAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('members', Member::class);
    }

    /**
     * Returns a PNG image widget for the guild. Requires no permissions or
     * authentication.
     *
     * @param string $style Style of the widget image returned (default 'shield').
     *
     * @return string
     */
    public function getImageAttribute(string $style = self::STYLE_SHIELD): string
    {
        $endpoint = Endpoint::bind(Endpoint::GUILD_WIDGET_IMAGE, $this->id);

        return Http::BASE_URL.'/'.self::withStyle($endpoint, $style);
    }

    /**
     * Downloads the guild's PNG widget image. Requires no permissions or authentication.
     *
     * @link https://docs.discord.com/developers/resources/guild#get-guild-widget-image
     *
     * @param string $style Style of the widget image returned (default 'shield').
     *
     * @return PromiseInterface<string> The PNG image's bytes.
     *
     * @since 10.64.0
     */
    public function getImage(string $style = self::STYLE_SHIELD): PromiseInterface
    {
        if (null === $driver = $this->http->getDriver()) {
            return reject(new \RuntimeException('HTTP driver is missing.'));
        }

        $endpoint = Endpoint::bind(Endpoint::GUILD_WIDGET_IMAGE, $this->id);

        // The HTTP client decodes every response as JSON, so the image is requested through its driver.
        return $driver->runRequest(new Request(new Deferred(), 'get', self::withStyle($endpoint, $style), '', ['User-Agent' => $this->http->getUserAgent()]))
            ->then(function (ResponseInterface $response): string {
                $status = $response->getStatusCode();
                if ($status < 200 || $status >= 300) {
                    throw new \RuntimeException("Could not get the widget image of guild {$this->id}: HTTP {$status} {$response->getReasonPhrase()}", $status);
                }

                return (string) $response->getBody();
            });
    }

    /**
     * Adds the widget image style to its endpoint, when it is one Discord knows.
     */
    private static function withStyle(Endpoint $endpoint, string $style): Endpoint
    {
        if (in_array(strtolower($style), self::STYLE)) {
            $endpoint->addQuery('style', strtolower($style));
        }

        return $endpoint;
    }
}
