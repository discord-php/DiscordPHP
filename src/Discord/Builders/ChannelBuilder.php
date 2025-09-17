<?php

declare(strict_types=1);

namespace Discord\Builders;

use Discord\Http\Exceptions\RequestFailedException;
use JsonSerializable;

/**
 * Helper class used to build guild channels.
 *
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

    public function setName(string $name): self
    {
        if (mb_strlen($name) < 1 || mb_strlen($name) > 100) {
            throw new \LengthException('Channel name must be between 1 and 100 characters.');
        }
        $this->name = $name;
        return $this;
    }

    public function setType(int $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setTopic(?string $topic): self
    {
        if ($topic !== null && mb_strlen($topic) > 1024) {
            throw new \LengthException('Channel topic must be 0-1024 characters.');
        }
        $this->topic = $topic;
        return $this;
    }

    public function setBitrate(?int $bitrate): self
    {
        $this->bitrate = $bitrate;
        return $this;
    }

    public function setUserLimit(?int $user_limit): self
    {
        $this->user_limit = $user_limit;
        return $this;
    }

    public function setRateLimitPerUser(?int $rate_limit): self
    {
        $this->rate_limit_per_user = $rate_limit;
        return $this;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function setPermissionOverwrites(?array $overwrites): self
    {
        $this->permission_overwrites = $overwrites;
        return $this;
    }

    public function setParentId(?string $parent_id): self
    {
        $this->parent_id = $parent_id;
        return $this;
    }

    public function setNsfw(?bool $nsfw): self
    {
        $this->nsfw = $nsfw;
        return $this;
    }

    public function setRtcRegion(?string $rtc_region): self
    {
        $this->rtc_region = $rtc_region;
        return $this;
    }

    public function setVideoQualityMode(?int $mode): self
    {
        $this->video_quality_mode = $mode;
        return $this;
    }

    public function setDefaultAutoArchiveDuration(?int $duration): self
    {
        $this->default_auto_archive_duration = $duration;
        return $this;
    }

    public function setDefaultReactionEmoji(?array $emoji): self
    {
        $this->default_reaction_emoji = $emoji;
        return $this;
    }

    public function setAvailableTags(?array $tags): self
    {
        $this->available_tags = $tags;
        return $this;
    }

    public function setDefaultSortOrder(?int $order): self
    {
        $this->default_sort_order = $order;
        return $this;
    }

    public function setDefaultForumLayout(?int $layout): self
    {
        $this->default_forum_layout = $layout;
        return $this;
    }

    public function setDefaultThreadRateLimitPerUser(?int $rate_limit): self
    {
        $this->default_thread_rate_limit_per_user = $rate_limit;
        return $this;
    }

    public function jsonSerialize(): array
    {
        if ($this->name === null) {
            throw new RequestFailedException('Channel name is required.');
        }

        $body = [
            'name' => $this->name,
        ];

        if (isset($this->type)) $body['type'] = $this->type;
        if (isset($this->topic)) $body['topic'] = $this->topic;
        if (isset($this->bitrate)) $body['bitrate'] = $this->bitrate;
        if (isset($this->user_limit)) $body['user_limit'] = $this->user_limit;
        if (isset($this->rate_limit_per_user)) $body['rate_limit_per_user'] = $this->rate_limit_per_user;
        if (isset($this->position)) $body['position'] = $this->position;
        if (isset($this->permission_overwrites)) $body['permission_overwrites'] = $this->permission_overwrites;
        if (isset($this->parent_id)) $body['parent_id'] = $this->parent_id;
        if (isset($this->nsfw)) $body['nsfw'] = $this->nsfw;
        if (isset($this->rtc_region)) $body['rtc_region'] = $this->rtc_region;
        if (isset($this->video_quality_mode)) $body['video_quality_mode'] = $this->video_quality_mode;
        if (isset($this->default_auto_archive_duration)) $body['default_auto_archive_duration'] = $this->default_auto_archive_duration;
        if (isset($this->default_reaction_emoji)) $body['default_reaction_emoji'] = $this->default_reaction_emoji;
        if (isset($this->available_tags)) $body['available_tags'] = $this->available_tags;
        if (isset($this->default_sort_order)) $body['default_sort_order'] = $this->default_sort_order;
        if (isset($this->default_forum_layout)) $body['default_forum_layout'] = $this->default_forum_layout;
        if (isset($this->default_thread_rate_limit_per_user)) $body['default_thread_rate_limit_per_user'] = $this->default_thread_rate_limit_per_user;

        return $body;
    }
}
