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

namespace Discord\Parts\Gateway;

use Discord\Parts\Part;
use Discord\Parts\User\Activity;

/**
 * Sent by the client to indicate a presence or status update.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#update-presence
 *
 * @since 10.55.2
 *
 * @property int|null   $since      Unix time (in milliseconds) of when the client went idle, or null if the client is not idle.
 * @property Activity[] $activities User's activities.
 * @property string     $status     User's new status.
 * @property bool       $afk        Whether or not the client is afk.
 */
class UpdatePresence extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'since',
        'activities',
        'status',
        'afk',
    ];

    /**
     * Sets the user's activities.
     *
     * @param Activity[] $activities
     *
     * @return self
     */
    public function setActivities(array $activities = []): self
    {
        $this->activities = $activities;

        return $this;
    }

    /**
     * Adds an activity to the user's presence.
     * 
     * @param Activity $activity The activity to add.
     * 
     * @return self
     */
    public function addActivity(Activity $activity): self
    {
        $activities = $this->activities ?? [];
        $activities[] = $activity;

        $this->activities = $activities;

        return $this;
    }

    /**
     * Removes an activity from the user's presence.
     *
     * @param Activity $activity The activity to remove.
     *
     * @return self
     */
    public function removeActivity(Activity $activity): self
    {
        $activities = $this->activities ?? [];
        if (($idx = array_search($activity, $activities)) !== false) {
            array_splice($activities, $idx, 1);
        }

        $this->activities = $activities;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $data = [
            'since' => $this->since ?? null,
            'status' => $this->status,
            'afk' => $this->afk,
            'activities' => $this->activities ?? [],
        ];

        return $data;
    }
}
