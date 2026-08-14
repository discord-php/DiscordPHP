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

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;
use Discord\Parts\User\Activity;

/**
 * Sent by the client to indicate a presence or status update.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#update-presence
 *
 * @since 10.55.1
 *
 * @property ?int|null                                  $since      Unix time (in milliseconds) of when the client went idle, or null if the client is not idle.
 * @property ExCollectionInterface<Activity>|Activity[] $activities User's activities.
 * @property string                                     $status     User's new status.
 * @property bool                                       $afk        Whether or not the client is afk.
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
     * Gets the activities attribute.
     *
     * @return ExCollectionInterface<Activity> The activities collection.
     */
    protected function getActivitiesAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('activities', Activity::class);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $data = [
            'status' => $this->status,
            'afk' => $this->afk,
        ];

        if (isset($this->since)) {
            $data['since'] = $this->since;
        }

        if (isset($this->activities)) {
            $data['activities'] = $this->activities;
        }

        return $data;
    }
}
