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

namespace Discord\Repository;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\User;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use React\Promise\PromiseInterface;

/**
 * Contains voice states of users in a guild.
 *
 * @see VoiceStateUpdate
 *
 * @since 10.34.0
 *
 * @method User|null get(string $discrim, $key)
 * @method User|null pull(string|int $key, $default = null)
 * @method User|null first()
 * @method User|null last()
 * @method User|null find(callable $callback)
 */
class VoiceStateRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'get' => Endpoint::GUILD_USER_VOICE_STATE,
        'update' => Endpoint::GUILD_USER_VOICE_STATE,
    ];

    /**
     * @inheritDoc
     */
    protected $class = VoiceStateUpdate::class;

    /**
     * Gets the voice regions available.
     *
     * @link https://discord.com/developers/docs/resources/voice#list-voice-regions
     *
     * @return PromiseInterface<Collection>
     *
     * @deprecated 10.23.0 Use `Discord::listVoiceRegions` instead.
     */
    public function getVoiceRegions(): PromiseInterface
    {
        return $this->discord->listVoiceRegions();
    }

    /**
     * Returns the current user's voice state in the guild.
     *
     * @link https://discord.com/developers/docs/resources/voice#get-current-user-voice-state
     *
     * @param Guild|string $guild The guild or guild ID.
     *
     * @return PromiseInterface<VoiceStateUpdate>
     *
     * @since 10.26.0
     */
    public function getCurrentUserVoiceState($guild): PromiseInterface
    {
        if (! is_string($guild)) {
            $guild = $guild->id;
        }

        return $this->http->get(Endpoint::bind(Endpoint::GUILD_USER_CURRENT_VOICE_STATE, $guild))->then(function ($response) {
            $part = $this->factory->part(VoiceStateUpdate::class, (array) $response, true);

            return $this->cache->set($part->{$this->discrim}, $part)->then(fn ($success) => $part);
        });
    }

    /**
     * Modify the current user's voice state in the guild.
     *
     * Caveats:
     * - channel_id must currently point to a stage channel.
     * - Current user must already have joined channel_id.
     * - You must have the MUTE_MEMBERS permission to unsuppress yourself. You can always suppress yourself.
     * - You must have the REQUEST_TO_SPEAK permission to request to speak. You can always clear your own request to speak.
     * - You are able to set request_to_speak_timestamp to any present or future time.
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-current-user-voice-state
     *
     * @param Guild|string        $guild                              The guild or guild ID.
     * @param array               $data
     * @param ?string|null        $data['channel_id']                 The ID of the channel the user is currently in.
     * @param ?bool|null          $data['suppress']                   Toggles the user's suppress state.
     * @param ?Carbon|string|null $data['request_to_speak_timestamp'] ISO8601 timestamp to set the user's request to speak.
     *
     * @return PromiseInterface
     */
    public function modifyCurrentUserVoiceState($guild, array $data): PromiseInterface
    {
        if (! is_string($guild)) {
            $guild = $guild->id;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_USER_CURRENT_VOICE_STATE, $guild), $data);
    }

    /**
     * Returns the specified user's voice state in the guild.
     *
     * @link https://discord.com/developers/docs/resources/voice#get-user-voice-state
     *
     * @param Guild|string       $guild The guild or guild ID.
     * @param Member|User|string $user  The user or user ID.
     *
     * @return PromiseInterface<VoiceStateUpdate>
     */
    public function getUserVoiceState($guild, $user): PromiseInterface
    {
        if (! is_string($guild)) {
            $guild = $guild->id;
        }

        if (! is_string($user)) {
            $user = $user->id;
        }

        return $this->http->get(Endpoint::bind(Endpoint::GUILD_USER_VOICE_STATE, $guild, $user))->then(function ($response) {
            $part = $this->factory->part(VoiceStateUpdate::class, (array) $response, true);

            return $this->cache->set($part->{$this->discrim}, $part)->then(fn ($success) => $part);
        });
    }

    /**
     * Updates another user's voice state.
     *
     * Caveats:
     * - channel_id must currently point to a stage channel.
     * - User must already have joined channel_id.
     * - You must have the MUTE_MEMBERS permission. (Since suppression is the only thing that is available currently.)
     * - When unsuppressed, non-bot users will have their request_to_speak_timestamp set to the current time. Bot users will not.
     * - When suppressed, the user will have their request_to_speak_timestamp removed.
     *
     * @link https://discord.com/developers/docs/resources/voice#modify-user-voice-state
     *
     * @param Guild|string $guild              The guild or guild ID.
     * @param array        $data
     * @param ?string|null $data['channel_id'] The ID of the channel the user is currently in.
     * @param ?bool|null   $data['suppress']   Toggles the user's suppress state.
     *
     * @return PromiseInterface
     */
    public function modifyUserVoiceState($guild, $user, array $data): PromiseInterface
    {
        if (! is_string($guild)) {
            $guild = $guild->id;
        }

        if (! is_string($user)) {
            $user = $user->id;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_USER_VOICE_STATE, $guild, $user), $data);
    }
}
