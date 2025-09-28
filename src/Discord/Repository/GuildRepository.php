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
use Discord\Parts\Guild\GuildPreview;
use Discord\Parts\User\User;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * Contains guilds that the client is in.
 *
 * @see Guild
 *
 * @since 4.0.0
 *
 * @method Guild|null get(string $discrim, $key)
 * @method Guild|null pull(string|int $key, $default = null)
 * @method Guild|null first()
 * @method Guild|null last()
 * @method Guild|null find(callable $callback)
 */
class GuildRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::USER_CURRENT_GUILDS,
        'get' => Endpoint::GUILD,
        'create' => Endpoint::GUILDS,
        'update' => Endpoint::GUILD,
        'delete' => Endpoint::GUILD,
        'leave' => Endpoint::USER_CURRENT_GUILD,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Guild::class;

    /**
     * Causes the client to leave a guild.
     *
     * @link https://discord.com/developers/docs/resources/user#leave-guild
     *
     * @param Guild|string $guild
     *
     * @return PromiseInterface<self>
     */
    public function leave($guild): PromiseInterface
    {
        if ($guild instanceof Guild) {
            $guild = $guild->id;
        }

        return $this->http
            ->delete(Endpoint::bind(Endpoint::USER_CURRENT_GUILD, $guild))
            ->then(fn () => $this->cache->delete($guild)->then(fn ($success) => $this));
    }

    /**
     * Returns the guild preview object for the given id. If the bot is not in the guild, then the guild must be discoverable.
     * Rejects with 10004 Unknown Guild if the guild does not exist or the bot is not in the guild and it is not discoverable.
     *
     * @link https://discord.com/developers/docs/resources/guild#get-guild-preview
     *
     * @param Guild|string $guild_id
     *
     * @return PromiseInterface<?GuildPreview>
     *
     * @since 10.19.0
     */
    public function preview($guild_id): PromiseInterface
    {
        if ($guild_id instanceof Guild) {
            $guild_id = $guild_id->id;
        }

        return $this->http->get(Endpoint::bind(Endpoint::GUILD_PREVIEW, $guild_id))
            ->then(fn ($data) => $data ? $this->factory->create(GuildPreview::class, $data, true) : null);
    }

    /**
     * Returns a list of partial guild objects the current user is a member of.
     * For OAuth2, requires the guilds scope.
     *
     * This endpoint returns 200 guilds by default, which is the maximum number of guilds a non-bot user can join.
     * Therefore, pagination is not needed for integrations that need to get a list of the users' guilds.
     *
     * @link https://discord.com/developers/docs/resources/user#get-current-user-guilds
     *
     * @param ?string|null $before      Get guilds before this guild ID.
     * @param ?string|null $after       Get guilds after this guild ID.
     * @param ?int|null    $limit       Max number of guilds to return (1-200). Defaults to 200.
     * @param ?bool|null   $with_counts Include approximate member and presence counts in response. Defaults to false.
     *
     * @throws \InvalidArgumentException No valid parameters to query.
     *
     * @return PromiseInterface<self>
     *
     * @since 10.32.0
     */
    public function getCurrentUserGuilds(array $params): PromiseInterface
    {
        $allowed = ['before', 'after', 'limit', 'with_counts'];
        $params = array_filter(
            $params,
            fn ($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($params)) {
            throw new \InvalidArgumentException('No valid parameters to query.');
        }

        return $this->http->get(Endpoint::USER_CURRENT_GUILDS, $params)->then(function ($response) {
            $promise = resolve(true);

            foreach ($response as $data) {
                $promise = $promise
                    ->then(fn ($success) => $this->cache->get($data->id))
                    ->then(function ($part) use ($data) {
                        if ($part !== null) {
                            return $this->cache->set($data->id, $part->fill($data));
                        }

                        return $this->cache->set($data->id, $this->factory->create(Guild::class, $data, true));
                    });
            }

            return $promise;
        })->then(fn ($success) => $this);
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
        if ($guild instanceof Guild) {
            $guild = $guild->id;
        }

        return $this->http->get(Endpoint::bind(Endpoint::GUILD_USER_CURRENT_VOICE_STATE), $guild)
            ->then(fn ($response) => $this->factory->part(VoiceStateUpdate::class, (array) $response, true));
    }

    /**
     * Returns the specified user's voice state in the guild.
     *
     * @link https://discord.com/developers/docs/resources/voice#get-user-voice-state
     *
     * @param User|string $user The user or user ID.
     *
     * @return PromiseInterface<VoiceStateUpdate>
     */
    public function getUserVoiceState($guild, $user): PromiseInterface
    {
        if ($user instanceof User) {
            $user = $user->id;
        }

        return $this->http->get(Endpoint::bind(Endpoint::GUILD_USER_VOICE_STATE, $guild, $user))
            ->then(fn ($response) => $this->factory->part(VoiceStateUpdate::class, (array) $response, true));
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
        if ($guild instanceof Guild) {
            $guild = $guild->id;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_USER_CURRENT_VOICE_STATE, $guild), $data);
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
        if ($guild instanceof Guild) {
            $guild = $guild->id;
        }

        if ($user instanceof User) {
            $user = $user->id;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_USER_VOICE_STATE, $guild, $user), $data);
    }
}
