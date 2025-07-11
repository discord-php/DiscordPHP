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

namespace Discord\Parts\WebSockets;

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\Activity;
use Discord\Parts\User\User;

/**
 * A PresenceUpdate part is used when the `PRESENCE_UPDATE` event is fired on
 * the WebSocket. It contains information about the users presence such as their
 * status (online/away) and their current game.
 *
 * @since 2.1.3
 *
 * @link https://discord.com/developers/docs/topics/gateway-events#presence
 *
 * @property      User                             $user           The user that the presence update affects.
 * @property      string                           $guild_id       The unique identifier of the guild that the presence update affects.
 * @property-read Guild|null                       $guild          The guild that the presence update affects.
 * @property      string                           $status         The updated status of the user.
 * @property      ExCollectionInterface|Activity[] $activities     The activities of the user.
 * @property-read Activity                         $game           The updated game of the user.
 * @property      object                           $client_status  Status of the client.
 * @property      string|null                      $desktop_status Status of the user on their desktop client. Null if they are not active on desktop.
 * @property      string|null                      $mobile_status  Status of the user on their mobile client. Null if they are not active on mobile.
 * @property      string|null                      $web_status     Status of the user on their web client. Null if they are not active on web.
 *
 * @property-read Member                       $member The member that the presence update affects.
 * @property-read ExCollectionInterface|Role[] $roles  Roles that the user has in the guild.
 */
class PresenceUpdate extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'user',
        'guild_id',
        'status',
        'activities',
        'client_status',
    ];

    /**
     * {@inheritDoc}
     */
    protected $visible = [
        'game',

        // @internal
        'member',
        'roles',
    ];

    /**
     * Gets the user attribute.
     *
     * @return User The user that had their presence updated.
     */
    protected function getUserAttribute(): User
    {
        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->factory->part(User::class, (array) $this->attributes['user'], true);
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild|null The guild that the user was in.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the activities attribute.
     *
     * @return ExCollectionInterface|Activity[]
     */
    protected function getActivitiesAttribute(): ExCollectionInterface
    {
        $collection = Collection::for(Activity::class, null);

        foreach ($this->attributes['activities'] ?? [] as $activity) {
            $collection->pushItem($this->factory->part(Activity::class, (array) $activity, true));
        }

        return $collection;
    }

    /**
     * Gets the game attribute.
     *
     * @return Activity|null The game attribute.
     */
    protected function getGameAttribute(): ?Activity
    {
        return $this->activities->get('type', Activity::TYPE_GAME);
    }

    /**
     * Gets the status of the user on their desktop client.
     *
     * @return string|null
     */
    protected function getDesktopStatusAttribute(): ?string
    {
        return $this->client_status->desktop ?? null;
    }

    /**
     * Gets the status of the user on their mobile client.
     *
     * @return string|null
     */
    protected function getMobileStatusAttribute(): ?string
    {
        return $this->client_status->mobile ?? null;
    }

    /**
     * Gets the status of the user on their web client.
     *
     * @return string|null
     */
    protected function getWebStatusAttribute(): ?string
    {
        return $this->client_status->web ?? null;
    }

    /**
     * Gets the member attribute.
     *
     * @return Member|null
     */
    protected function getMemberAttribute(): ?Member
    {
        if (isset($this->attributes['user']) && $guild = $this->guild) {
            return $guild->members->get('id', $this->attributes['user']->id);
        }

        return null;
    }

    /**
     * Returns the users roles.
     *
     * @return ExCollectionInterface|Role[]
     */
    protected function getRolesAttribute(): ExCollectionInterface
    {
        if ($member = $this->member) {
            return $member->roles;
        }

        return Collection::for(Role::class);
    }
}
