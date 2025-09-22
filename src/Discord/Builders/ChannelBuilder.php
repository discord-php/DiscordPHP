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

namespace Discord\Builders;

use Discord\Http\Exceptions\RequestFailedException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Overwrite;
use Discord\Parts\Guild\Emoji;
use Discord\Voice\Region;
use JsonSerializable;

/**
 * Helper class used to build guild channels.
 *
 * @link https://discord.com/developers/docs/resources/guild#create-guild-channel
 *
 * @since 10.23.0 Added data validation and support for Parts as parameters.
 * @since 10.21.0
 */
class ChannelBuilder extends Builder implements JsonSerializable
{
    protected string $name;
    protected ?int $type;
    protected ?string $topic; // Text, Announcement, Forum, Media
    protected ?int $bitrate; // Voice, Stage
    protected ?int $user_limit; // Voice, Stage
    protected ?int $rate_limit_per_user; // Text, Voice, Stage, Forum, Media
    protected ?int $position;
    protected ?array $permission_overwrites;
    protected ?string $parent_id; // Text, Voice, Announcement, Stage, Forum, Media
    protected ?bool $nsfw; // Text, Voice, Announcement, Stage, Forum
    protected ?string $rtc_region; // Voice, Stage
    protected ?int $video_quality_mode; // Voice, Stage
    protected ?int $default_auto_archive_duration; // Text, Announcement, Forum, Media
    protected ?array $default_reaction_emoji; // Forum, Media
    protected ?array $available_tags; // Forum, Media
    protected ?int $default_sort_order; // Forum, Media
    protected ?int $default_forum_layout; // Forum
    protected ?int $default_thread_rate_limit_per_user; // Text, Announcement, Forum, Media

    public static function new(string $name): self
    {
        return (new static())->setName($name);
    }

    /**
     * Sets the channel name.
     *
     * @param string $name The channel name (1-100 characters).
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        if (mb_strlen($name) < 1 || mb_strlen($name) > 100) {
            throw new \LengthException('Channel name must be between 1 and 100 characters.');
        }
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the channel type.
     *
     * @param int $type The channel type. Must be one of the TYPE_* constants on the Channel class.
     *
     * @return $this
     */
    public function setType(int $type): self
    {
        $allowed = [
            Channel::TYPE_GUILD_TEXT,
            Channel::TYPE_DM,
            Channel::TYPE_GUILD_VOICE,
            Channel::TYPE_GROUP_DM,
            Channel::TYPE_GUILD_CATEGORY,
            Channel::TYPE_GUILD_ANNOUNCEMENT,
            Channel::TYPE_ANNOUNCEMENT_THREAD,
            Channel::TYPE_PUBLIC_THREAD,
            Channel::TYPE_PRIVATE_THREAD,
            Channel::TYPE_GUILD_STAGE_VOICE,
            Channel::TYPE_GUILD_DIRECTORY,
            Channel::TYPE_GUILD_FORUM,
            Channel::TYPE_GUILD_MEDIA,
        ];

        if (!in_array($type, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid channel type specified.');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Sets the channel topic for Text, Announcement, Forum, and Media channels.
     *
     * @param string|null $topic The channel topic (0-1024 characters).
     *
     * @return $this
     */
    public function setTopic(?string $topic = null): self
    {
        if ($topic !== null && mb_strlen($topic) > 1024) {
            throw new \LengthException('Channel topic must be 0-1024 characters.');
        }

        $this->topic = $topic;

        return $this;
    }

    /**
     * Sets the bitrate for Voice and Stage channels.
     *
     * @param int|null $bitrate The bitrate in bits (minimum 8000).
     *
     * @return $this
     */
    public function setBitrate(?int $bitrate = null): self
    {
        if ($bitrate !== null && ($bitrate < 8000)) {
            throw new \OutOfRangeException('Bitrate must be at least 8000.');
        }

        $this->bitrate = $bitrate;

        return $this;
    }

    /**
     * Sets the user limit for Voice and Stage channels.
     *
     * @param int|null $user_limit The user limit (0-99 for Voice, 0-10,000 for Stage). 0 is unlimited.
     *
     * @return $this
     */
    public function setUserLimit(?int $user_limit = null): self
    {
        $this->user_limit = $user_limit;

        return $this;
    }

    /**
     * Sets the rate limit per user for Text, Voice, Stage, Forum, and Media channels.
     *
     * @param int|null $rate_limit The rate limit per user in seconds (0-21600).
     *
     * @return $this
     */
    public function setRateLimitPerUser(?int $rate_limit = null): self
    {
        $this->rate_limit_per_user = $rate_limit;

        return $this;
    }

    /**
     * Sets the position of the channel.
     *
     * @param int|null $position The position of the channel.
     *
     * @return $this
     */
    public function setPosition(?int $position = null): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Sets the permission overwrites for the channel.
     *
     * @param Overwrite[]|null $overwrites An array of permission overwrite arrays.
     *
     * @return $this
     */
    public function setPermissionOverwrites(?array $overwrites = null): self
    {
        $this->permission_overwrites = $overwrites;

        return $this;
    }

    /**
     * Sets the parent category ID for Text, Voice, Announcement, Stage, Forum, and Media channels.
     *
     * @param Channel|string|null $parent_id The parent category ID.
     *
     * @return $this
     */
    public function setParentId(Channel|string|null $parent_id = null): self
    {
        if ($parent_id instanceof Channel) {
            $parent_id = $parent_id->id;
        }

        $this->parent_id = $parent_id;

        return $this;
    }

    /**
     * Sets whether the channel is NSFW for Text, Voice, Announcement, Stage, and Forum channels.
     *
     * @param bool|null $nsfw Whether the channel is NSFW.
     *
     * @return $this
     */
    public function setNsfw(?bool $nsfw = null): self
    {
        $this->nsfw = $nsfw;

        return $this;
    }

    /**
     * Sets the RTC region for Voice and Stage channels.
     *
     * @param Region|string|null $rtc_region The RTC region ID, or null for automatic.
     *
     * @return $this
     */
    public function setRtcRegion(Region|string|null $rtc_region = null): self
    {
        if ($rtc_region instanceof Region) {
            $rtc_region = $rtc_region->id;
        }

        $this->rtc_region = $rtc_region;

        return $this;
    }

    /**
     * Sets the video quality mode for Voice and Stage channels.
     *
     * @param int|null $mode The video quality mode. 1 for Discord chooses the quality for optimal performance, 2 for full 720p.
     *
     * @return $this
     */
    public function setVideoQualityMode(?int $mode = null): self
    {
        if ($mode !== null && !in_array($mode, [1, 2])) {
            throw new \InvalidArgumentException('Invalid video quality mode specified. Must be 1 (Discord chooses the quality for optimal performance) or 2 (full 720p).');
        }

        $this->video_quality_mode = $mode;

        return $this;
    }

    /**
     * Sets the default auto archive duration for Text, Announcement, Forum, and Media channels.
     *
     * @param int|null $duration The default auto archive duration in minutes. Can be 60, 1440, 4320, or 10080.
     *
     * @return $this
     */
    public function setDefaultAutoArchiveDuration(?int $duration = null): self
    {
        $this->default_auto_archive_duration = $duration;

        return $this;
    }

    /**
     * Sets the default reaction emoji for the channel.
     *
     * @param Emoji|array|null $emoji
     *
     * @return $this
     */
    public function setDefaultReactionEmoji(Emoji|array|null $emoji = null): self
    {
        if ($emoji instanceof Emoji) {
            $emoji = [
                'emoji_id' => $emoji->id,
                'emoji_name' => $emoji->name,
            ];
        }

        $this->default_reaction_emoji = $emoji;

        return $this;
    }

    /**
     * Sets the available tags for the channel.
     *
     * @param Tag[]|null $tags
     *
     * @return $this
     */
    public function setAvailableTags(?array $tags = null): self
    {
        $this->available_tags = $tags;

        return $this;
    }

    /**
     * Sets the default sort order for Forum and Media channels.
     *
     * @param int|null $order The default sort order. 0 for Latest Activity, 1 for Creation Date.
     *
     * @return $this
     */
    public function setDefaultSortOrder(?int $order = null): self
    {
        $this->default_sort_order = $order;

        return $this;
    }

    /**
     * Sets the default forum layout for Forum channels.
     *
     * @param int|null $layout The default forum layout. 0 for Not Set, 1 for List View, 2 for Gallery View.
     */
    public function setDefaultForumLayout(?int $layout = null): self
    {
        $this->default_forum_layout = $layout;

        return $this;
    }

    /**
     * Sets the default thread rate limit per user for Text, Announcement, Forum, and Media channels.
     *
     * @param int|null $rate_limit The default thread rate limit per user in seconds (0-21600).
     *
     * @return $this
     */
    public function setDefaultThreadRateLimitPerUser(?int $rate_limit = null): self
    {
        $this->default_thread_rate_limit_per_user = $rate_limit;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        if ($this->name === null) {
            throw new RequestFailedException('Channel name is required.');
        }

        $body = [
            'name' => $this->name,
        ];

        if (isset($this->type)) {
            $body['type'] = $this->type;
        }
        if (isset($this->topic)) {
            $body['topic'] = $this->topic;
        }
        if (isset($this->bitrate)) {
            $body['bitrate'] = $this->bitrate;
        }
        if (isset($this->user_limit)) {
            $body['user_limit'] = $this->user_limit;
        }
        if (isset($this->rate_limit_per_user)) {
            $body['rate_limit_per_user'] = $this->rate_limit_per_user;
        }
        if (isset($this->position)) {
            $body['position'] = $this->position;
        }
        if (isset($this->permission_overwrites)) {
            $body['permission_overwrites'] = $this->permission_overwrites;
        }
        if (isset($this->parent_id)) {
            $body['parent_id'] = $this->parent_id;
        }
        if (isset($this->nsfw)) {
            $body['nsfw'] = $this->nsfw;
        }
        if (isset($this->rtc_region)) {
            $body['rtc_region'] = $this->rtc_region;
        }
        if (isset($this->video_quality_mode)) {
            $body['video_quality_mode'] = $this->video_quality_mode;
        }
        if (isset($this->default_auto_archive_duration)) {
            $body['default_auto_archive_duration'] = $this->default_auto_archive_duration;
        }
        if (isset($this->default_reaction_emoji)) {
            $body['default_reaction_emoji'] = $this->default_reaction_emoji;
        }
        if (isset($this->available_tags)) {
            $body['available_tags'] = $this->available_tags;
        }
        if (isset($this->default_sort_order)) {
            $body['default_sort_order'] = $this->default_sort_order;
        }
        if (isset($this->default_forum_layout)) {
            $body['default_forum_layout'] = $this->default_forum_layout;
        }
        if (isset($this->default_thread_rate_limit_per_user)) {
            $body['default_thread_rate_limit_per_user'] = $this->default_thread_rate_limit_per_user;
        }

        return $body;
    }
}
