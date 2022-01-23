<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Carbon\Carbon;
use Discord\Builders\MessageBuilder;
use Discord\Helpers\Bitwise;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Overwrite;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\Permissions\Permission;
use Discord\Parts\Permissions\RolePermission;
use Discord\Parts\WebSockets\PresenceUpdate;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\reject;

/**
 * A member is a relationship between a user and a guild. It contains user-to-guild specific data like roles.
 *
 * @see https://discord.com/developers/docs/resources/guild#guild-member-object
 *
 * @property User|null             $user                         The user part of the member.
 * @property string|null           $nick                         The nickname of the member.
 * @property string|null           $avatar                       The avatar URL of the member or null if member has no guild avatar.
 * @property string|null           $avatar_hash                  The avatar hash of the member or null if member has no guild avatar.
 * @property Collection|Role[]     $roles                        A collection of Roles that the member has.
 * @property Carbon|null           $joined_at                    A timestamp of when the member joined the guild.
 * @property Carbon|null           $premium_since                When the user started boosting the server.
 * @property bool                  $deaf                         Whether the member is deaf.
 * @property bool                  $mute                         Whether the member is mute.
 * @property bool                  $pending                      Whether the user has not yet passed the guild's Membership Screening requirements.
 * @property string|null           $permissions
 * @property Carbon|null           $communication_disabled_until When the user's timeout will expire and the user will be able to communicate in the guild again, null or a time in the past if the user is not timed out.
 * @property string                $id                           The unique identifier of the member.
 * @property string                $username                     The username of the member.
 * @property string                $discriminator                The discriminator of the member.
 * @property string                $displayname                  The nickname or username with discriminator of the member.
 * @property Guild                 $guild                        The guild that the member belongs to.
 * @property string                $guild_id                     The unique identifier of the guild that the member belongs to.
 * @property string                $status                       The status of the member.
 * @property Activity              $game                         The game the member is playing.
 * @property Collection|Activity[] $activities                   User's current activities.
 * @property object                $client_status                Current client status.
 *
 * @method ExtendedPromiseInterface sendMessage(MessageBuilder $builder)
 * @method ExtendedPromiseInterface sendMessage(string $text, bool $tts = false, Embed|array $embed = null, array $allowed_mentions = null, ?Message $replyTo = null)
 */
class Member extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'user',
        'nick',
        'avatar',
        'roles',
        'joined_at',
        'premium_since',
        'deaf',
        'mute',
        'pending',
        'guild_id',
        'status',
        'communication_disabled_until',
        'id',
        'activities',
        'client_status',
    ];

    /**
     * @inheritdoc
     */
    protected $fillAfterSave = false;

    /**
     * Updates the member from a new presence update object.
     * This is an internal function and is not meant to be used by a public application.
     *
     * @internal
     *
     * @param PresenceUpdate $presence
     *
     * @throws \Exception
     *
     * @return PresenceUpdate Old presence.
     */
    public function updateFromPresence(PresenceUpdate $presence): Part
    {
        $rawPresence = $presence->getRawAttributes();
        $oldPresence = $this->factory->create(PresenceUpdate::class, $this->attributes, true);

        $this->attributes = array_merge($this->attributes, $rawPresence);

        return $oldPresence;
    }

    /**
     * Bans the member. Alias for `$guild->bans->ban()`.
     *
     * @param int|null    $daysToDeleteMessages The amount of days to delete messages from.
     * @param string|null $reason
     *
     * @throws \Exception
     *
     * @return ExtendedPromiseInterface
     */
    public function ban(?int $daysToDeleteMessages = null, ?string $reason = null): ExtendedPromiseInterface
    {
        return $this->guild->bans->ban($this, $daysToDeleteMessages, $reason);
    }

    /**
     * Sets the nickname of the member.
     *
     * @param string|null $nick   The nickname of the member.
     * @param string|null $reason Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface
     */
    public function setNickname(?string $nick = null, ?string $reason = null): ExtendedPromiseInterface
    {
        $payload = [
            'nick' => $nick ?? '',
        ];

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        // jake plz
        if ($this->discord->id == $this->id) {
            return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER_SELF, $this->guild_id), $payload, $headers);
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $this->id), $payload, $headers);
    }

    /**
     * Moves the member to another voice channel.
     *
     * @param Channel|string $channel The channel to move the member to.
     * @param string|null    $reason  Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface
     */
    public function moveMember($channel, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($channel instanceof Channel) {
            $channel = $channel->id;
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $this->id), ['channel_id' => $channel], $headers);
    }

    /**
     * Adds a role to the member.
     *
     * @param Role|string $role   The role to add to the member.
     * @param string|null $reason Reason for Audit Log.
     *
     * @throws \RuntimeException
     *
     * @return ExtendedPromiseInterface
     */
    public function addRole($role, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($role instanceof Role) {
            $role = $role->id;
        }

        // We don't want a double up on roles
        if (in_array($role, (array) $this->attributes['roles'])) {
            return reject(new \RuntimeException('Member already has role.'));
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->put(Endpoint::bind(Endpoint::GUILD_MEMBER_ROLE, $this->guild_id, $this->id, $role), null, $headers);
    }

    /**
     * Removes a role from the member.
     *
     * @param Role|string $role   The role to remove from the member.
     * @param string|null $reason Reason for Audit Log.
     *
     * @throws \RuntimeException
     *
     * @return ExtendedPromiseInterface
     */
    public function removeRole($role, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($role instanceof Role) {
            $role = $role->id;
        }

        if (in_array($role, $this->attributes['roles'])) {
            $headers = [];
            if (isset($reason)) {
                $headers['X-Audit-Log-Reason'] = $reason;
            }

            return $this->http->delete(Endpoint::bind(Endpoint::GUILD_MEMBER_ROLE, $this->guild_id, $this->id, $role), null, $headers);
        }

        return reject(new \RuntimeException('Member does not have role.'));
    }

    /**
     * Sends a message to the member.
     *
     * Takes a `MessageBuilder` or content of the message for the first parameter. If the first parameter
     * is an instance of `MessageBuilder`, the rest of the arguments are disregarded.
     *
     * @param MessageBuilder|string $message          The message builder that should be converted into a message, or the string content of the message.
     * @param bool                  $tts              Whether the message is TTS.
     * @param Embed|array|null      $embed            An embed object or array to send in the message.
     * @param array|null            $allowed_mentions Allowed mentions object for the message.
     * @param Message|null          $replyTo          Sends the message as a reply to the given message instance.
     *
     * @throws \RuntimeException
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function sendMessage($message, bool $tts = false, $embed = null, $allowed_mentions = null, ?Message $replyTo = null): ExtendedPromiseInterface
    {
        if ($this->user) {
            return $this->user->sendMessage($message, $tts, $embed, $allowed_mentions, $replyTo);
        }

        return reject(new \RuntimeException('Member had no user part.'));
    }

    /**
     * Gets the total permissions of the member.
     *
     * Note that Discord permissions are complex and YOU
     * need to account for the fact that you cannot edit
     * a role higher than your own.
     *
     * @see https://discord.com/developers/docs/topics/permissions
     *
     * @param Channel|null $channel
     *
     * @return RolePermission
     */
    public function getPermissions(?Channel $channel = null): RolePermission
    {
        $bitwise = $this->guild->roles->get('id', $this->guild_id)->permissions->bitwise;

        if ($this->guild->owner_id == $this->id) {
            $bitwise = Bitwise::set($bitwise, Permission::ROLE_PERMISSIONS['administrator']); // Add administrator permission
        } else {
            $roles = [];

            /* @var Role */
            foreach ($this->roles ?? [] as $role) {
                $roles[] = $role->id;
                $bitwise = Bitwise::or($bitwise, $role->permissions->bitwise);
            }
        }

        /** @var RolePermission */
        $newPermission = $this->factory->part(RolePermission::class, ['bitwise' => $bitwise]);

        if ($newPermission->administrator) {
            foreach (RolePermission::getPermissions() as $permission => $_) {
                $newPermission->{$permission} = true;
            }

            return $newPermission;
        }

        if ($channel) {
            /* @var Overwrite */
            if ($overwrite = $channel->overwrites->get('id', $this->guild->id)) {
                $bitwise = Bitwise::or($bitwise, $overwrite->allow->bitwise);
                $bitwise = Bitwise::and($bitwise, Bitwise::not($overwrite->deny->bitwise));
            }

            /* @var Overwrite */
            foreach ($channel->overwrites as $overwrite) {
                if ($overwrite->type !== Overwrite::TYPE_ROLE || ! in_array($overwrite->id, $roles)) {
                    continue;
                }

                $bitwise = Bitwise::or($bitwise, $overwrite->allow->bitwise);
                $bitwise = Bitwise::and($bitwise, Bitwise::not($overwrite->deny->bitwise));
            }

            /* @var Overwrite */
            if ($overwrite = $channel->overwrites->get('id', $this->id)) {
                $bitwise = Bitwise::or($bitwise, $overwrite->allow->bitwise);
                $bitwise = Bitwise::and($bitwise, Bitwise::not($overwrite->deny->bitwise));
            }
        }

        /** @var RolePermission */
        $newPermission = $this->factory->part(RolePermission::class, ['bitwise' => $bitwise]);

        return $newPermission;
    }

    /**
     * Sets timeout on a member.
     *
     * @param Carbon|null $communication_disabled_until When the user's timeout will expire and the user will be able to communicate in the guild again, null or a time in the past if the user is not timed out.
     *
     * @throws NoPermissionsException
     *
     * @return ExtendedPromiseInterface
     */
    public function timeoutMember(?Carbon $communication_disabled_until): ExtendedPromiseInterface
    {
        $botperms = $this->guild->members->offsetGet($this->discord->id)->getPermissions();

        if (! $botperms->moderate_members) {
            return reject(new NoPermissionsException('You do not have permission to time out members in the specified guild.'));
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $this->id), ['communication_disabled_until' => isset($communication_disabled_until) ? $communication_disabled_until->toIso8601ZuluString() : null]);
    }

    /**
     * Returns the member nickname or username with the discriminator.
     *
     * @return string Nickname#Discriminator
     */
    protected function getDisplaynameAttribute(): string
    {
        return ($this->nick ?? $this->username) . '#' . $this->discriminator;
    }

    /**
     * Gets the game attribute.
     * Polyfill for the first activity.
     *
     * @return Activity
     */
    protected function getGameAttribute(): ?Activity
    {
        return $this->activities->first();
    }

    /**
     * Gets the activities attribute.
     *
     * @throws \Exception
     *
     * @return Collection|Activity[]
     */
    protected function getActivitiesAttribute(): Collection
    {
        $activities = new Collection([], null);

        foreach ($this->attributes['activities'] ?? [] as $activity) {
            $activities->push($this->factory->create(Activity::class, $activity, true));
        }

        return $activities;
    }

    /**
     * Returns the id attribute.
     *
     * @return string The user ID of the member.
     */
    protected function getIdAttribute(): string
    {
        return $this->attributes['id'] ?? $this->attributes['user']->id;
    }

    /**
     * Returns the username attribute.
     *
     * @return string The username of the member.
     */
    protected function getUsernameAttribute(): string
    {
        return $this->user->username;
    }

    /**
     * Returns the discriminator attribute.
     *
     * @return string The discriminator of the member.
     */
    protected function getDiscriminatorAttribute(): string
    {
        return $this->user->discriminator;
    }

    /**
     * Returns the user attribute.
     *
     * @return User|null The user that owns the member.
     */
    protected function getUserAttribute(): ?User
    {
        if (! isset($this->attributes['user'])) {
            return null;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->factory->part(User::class, (array) $this->attributes['user'], true);
    }

    /**
     * Returns the guild attribute.
     *
     * @return null|Guild The guild.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->offsetGet($this->guild_id);
    }

    /**
     * Returns the roles attribute.
     *
     * @throws \Exception
     *
     * @return Collection A collection of roles the member is in.
     */
    protected function getRolesAttribute(): Collection
    {
        $roles = new Collection();

        if ($guild = $this->guild) {
            foreach ($guild->roles as $role) {
                if (in_array($role->id, $this->attributes['roles'] ?? [])) {
                    $roles->push($role);
                }
            }
        } else {
            foreach ($this->attributes['roles'] ?? [] as $role) {
                $roles->push($this->factory->create(Role::class, $role, true));
            }
        }

        return $roles;
    }

    /**
     * Returns the joined at attribute.
     *
     * @throws \Exception
     *
     * @return Carbon|null The timestamp from when the member joined.
     */
    protected function getJoinedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['joined_at'])) {
            return null;
        }

        return new Carbon($this->attributes['joined_at']);
    }

    /**
     * Returns the guild avatar URL for the member.
     *
     * @param string|null $format The image format.
     * @param int         $size   The size of the image.
     *
     * @return string|null The URL to the member avatar or null.
     */
    public function getAvatarAttribute(?string $format = null, int $size = 1024): ?string
    {
        if (! isset($this->attributes['avatar'])) {
            return null;
        }

        if (isset($format)) {
            $allowed = ['png', 'jpg', 'webp', 'gif'];

            if (! in_array(strtolower($format), $allowed)) {
                $format = 'webp';
            }
        } elseif (strpos($this->attributes['avatar'], 'a_') === 0) {
            $format = 'gif';
        } else {
            $format = 'webp';
        }

        return "https://cdn.discordapp.com/guilds/{$this->guild_id}/users/{$this->id}/avatars/{$this->attributes['avatar']}.{$format}?size={$size}";
    }

    /**
     * Returns the guild avatar hash for the member.
     *
     * @return string|null The member avatar's hash or null.
     */
    protected function getAvatarHashAttribute(): ?string
    {
        return $this->attributes['avatar'] ?? null;
    }

    /**
     * Returns the premium since attribute.
     *
     * @return Carbon|null
     */
    protected function getPremiumSinceAttribute(): ?Carbon
    {
        if (! isset($this->attributes['premium_since'])) {
            return null;
        }

        return Carbon::parse($this->attributes['premium_since']);
    }

    /**
     * Returns the communication disabled until attribute.
     *
     * @return Carbon|null
     */
    protected function getCommunicationDisabledUntilAttribute(): ?Carbon
    {
        if (! isset($this->attributes['communication_disabled_until'])) {
            return null;
        }

        return Carbon::parse($this->attributes['communication_disabled_until']);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'roles' => array_values($this->attributes['roles']),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'user_id' => $this->id,
        ];
    }

    /**
     * Returns a formatted mention.
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->nick) {
            return "<@!{$this->id}>";
        }

        return "<@{$this->id}>";
    }
}
