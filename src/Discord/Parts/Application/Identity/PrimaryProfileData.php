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

namespace Discord\Parts\Application\Identity;

use Discord\Parts\Part;

/**
 * Optional pre-configured game stats.
 *
 * @since 10.59.0
 * @link https://docs.discord.com/developers/resources/application-identity-profile#primary-profile-data-object
 *
 * @property string|null $season Current season.
 * @property string|null $rank_name Current rank.
 * @property ProfileMedia|null $rank_image Current rank image.
 * @property string|null $highest_rank Highest rank achieved.
 * @property ProfileMedia|null $highest_rank_image Highest rank image.
 * @property string|null $featured_played_character Featured character.
 * @property ProfileMedia|null $featured_played_character_image Featured character image.
 * @property int|float|null $playtime_hours Total hours played.
 * @property int|null $total_wins Total wins.
 * @property int|null $current_period_wins Wins this period.
 * @property int|null $total_games Total games.
 * @property int|null $current_period_games Games this period.
 * @property int|null $total_kills Total kills.
 * @property int|null $current_period_kills Kills this period.
 * @property int|null $total_assists Total assists.
 * @property int|null $current_period_assists Assists this period.
 * @property int|null $total_deaths Total deaths.
 * @property int|null $current_period_deaths Deaths this period.
 */
class PrimaryProfileData extends Part
{
    public const STRING_FIELDS = ['season', 'rank_name', 'highest_rank', 'featured_played_character'];
    public const MEDIA_FIELDS = ['rank_image', 'highest_rank_image', 'featured_played_character_image'];
    public const INTEGER_FIELDS = [
        'total_wins', 'current_period_wins', 'total_games', 'current_period_games',
        'total_kills', 'current_period_kills', 'total_assists', 'current_period_assists',
        'total_deaths', 'current_period_deaths',
    ];

    protected $fillable = [
        'season', 'rank_name', 'rank_image', 'highest_rank', 'highest_rank_image',
        'featured_played_character', 'featured_played_character_image', 'playtime_hours',
        'total_wins', 'current_period_wins', 'total_games', 'current_period_games',
        'total_kills', 'current_period_kills', 'total_assists', 'current_period_assists',
        'total_deaths', 'current_period_deaths',
    ];

    /** @return ProfileMedia|null */
    protected function getRankImageAttribute(): ?ProfileMedia
    {
        return $this->media('rank_image');
    }

    /** @return ProfileMedia|null */
    protected function getHighestRankImageAttribute(): ?ProfileMedia
    {
        return $this->media('highest_rank_image');
    }

    /** @return ProfileMedia|null */
    protected function getFeaturedPlayedCharacterImageAttribute(): ?ProfileMedia
    {
        return $this->media('featured_played_character_image');
    }

    /** Materialize a media view without replacing the raw attribute. */
    private function media(string $key): ?ProfileMedia
    {
        if (! isset($this->attributes[$key])) {
            return null;
        }

        return $this->attributes[$key] instanceof ProfileMedia
            ? $this->attributes[$key]
            : $this->createOf(ProfileMedia::class, $this->attributes[$key]);
    }

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        return $this->getRawAttributes();
    }
}