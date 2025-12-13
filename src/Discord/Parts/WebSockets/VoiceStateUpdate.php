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

use Carbon\Carbon;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\VoiceStateRepository;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * Notifies the client of voice state updates about users.
 *
 * @link https://discord.com/developers/docs/resources/voice#voice-state-object
 *
 * @since 3.2.1
 *
 * @property      ?string|null  $guild_id                   Guild ID that the voice state came from, or null if it is for a DM channel.
 * @property-read ?Guild|null   $guild                      Guild that the voice state came from, or null if it is for a DM channel.
 * @property      ?string|null  $channel_id                 Channel ID that the voice state came from, or null if the user is leaving a channel.
 * @property-read ?Channel|null $channel                    Channel that the voice state came from, or null if the user is leaving a channel.
 * @property      string        $user_id                    User ID the voice state is for.
 * @property-read User|null     $user                       User the voice state is for, or null if it is not cached.
 * @property      ?Member|null  $member                     Member object the voice state is for, null if the voice state is for a DM channel or the member object is not cached.
 * @property      string        $session_id                 Session ID for the voice state.
 * @property      bool          $deaf                       Whether this user is deafened by the server.
 * @property      bool          $mute                       Whether this user is muted by the server.
 * @property      bool          $self_deaf                  Whether this user is locally deafened.
 * @property      bool          $self_mute                  Whether this user is locally muted.
 * @property      ?bool|null    $self_stream                Whether this user is streaming using "Go Live".
 * @property      bool          $self_video                 Whether this user's camera is enabled.
 * @property      bool          $suppress                   Whether this user is muted by the current user.
 * @property      ?Carbon       $request_to_speak_timestamp The time at which the user requested to speak.
 */
class VoiceStateUpdate extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'guild_id',
        'channel_id',
        'user_id',
        'member',
        'session_id',
        'deaf',
        'mute',
        'self_deaf',
        'self_mute',
        'self_stream',
        'self_video',
        'suppress',
        'request_to_speak_timestamp',
    ];

    /**
     * Gets the guild attribute.
     *
     * @return Guild|null The guild attribute.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel|null The channel attribute.
     */
    protected function getChannelAttribute(): ?Channel
    {
        if (! isset($this->attributes['channel_id'])) {
            return null;
        }

        if ($this->channel_id && $guild = $this->guild) {
            return $guild->channels->get('id', $this->channel_id);
        }

        return $this->discord->getChannel($this->channel_id);
    }

    /**
     * Gets the user attribute.
     *
     * @return User|null The user attribute.
     */
    protected function getUserAttribute(): ?User
    {
        if ($user = $this->discord->users->get('id', $this->user_id)) {
            return $user;
        }

        if ($member = $this->member) {
            return $member->user;
        }

        return null;
    }

    /**
     * Gets the member attribute.
     *
     * @return Member|null The member attribute.
     */
    protected function getMemberAttribute(): ?Member
    {
        if ($guild = $this->guild) {
            if ($member = $guild->members->get('id', $this->user_id)) {
                return $member;
            }
        }

        return $this->attributePartHelper('member', Member::class, ['guild_id' => $this->guild_id]);
    }

    /**
     * Gets the request_to_speak_timestamp attribute.
     *
     * @return Carbon|null
     *
     * @throws \Exception
     */
    protected function getRequestToSpeakTimestampAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('request_to_speak_timestamp');
    }

    /**
     * Gets the originating repository of the part.
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return VoiceStateRepository|null The repository, or null if required part data is missing.
     */
    public function getRepository(): VoiceStateRepository|null
    {
        if (! isset($this->attributes['guild_id'], $this->attributes['user_id'])) {
            return null;
        }

        /** @var Guild $guild */
        $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);

        return $guild->voice_states;
    }

    /**
     * @inheritDoc
     */
    public function save(?string $reason = null): PromiseInterface
    {
        if (isset($this->attributes['guild_id'], $this->attributes['user_id'])) {
            /** @var Guild $guild */
            $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);

            if ($this->user_id !== $this->discord->id) {
                if ($botperms = $guild->getBotPermissions()) {
                    if (! $botperms->mute_members) {
                        return reject(new NoPermissionsException("You do not have permission to mute members in the guild {$guild->id}."));
                    }
                }
            }

            return $guild->voice_states->save($this, $reason);
        }

        return parent::save();
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_id' => $this->guild_id,
            'user_id' => $this->user_id,
        ];
    }
}
