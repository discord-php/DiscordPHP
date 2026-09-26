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

namespace Discord\Parts\Channel;

use Discord\Http\Endpoint;
use Discord\OAuth2\AccessToken;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;

/**
 * A direct message between multiple users.
 */
class GroupDM extends Channel
{
    /**
     * Adds a user to the group DM with their OAuth2 access token, which must have the `gdm.join` scope and
     * come from the bot's own application.
     *
     * @link https://docs.discord.com/developers/resources/channel#group-dm-add-recipient
     *
     * @param User|string        $user         The user to add.
     * @param AccessToken|string $access_token The user's access token.
     * @param string|null        $nick         Their nickname in the group DM.
     *
     * @return PromiseInterface<self>
     *
     * @since 10.60.0
     */
    public function addRecipient($user, $access_token, ?string $nick = null): PromiseInterface
    {
        $user_id = $user instanceof Part ? $user->id : (string) $user;
        $payload = ['access_token' => $access_token instanceof AccessToken ? $access_token->access_token : (string) $access_token];

        if (null !== $nick) {
            $payload['nick'] = $nick;
        }

        return $this->http->put(Endpoint::bind(Endpoint::CHANNEL_RECIPIENT, $this->id, $user_id), $payload)
            ->then(function ($response) use ($user) {
                // Discord answers with the channel, or with no content when the user was already in it.
                if (is_object($response) && isset($response->recipients)) {
                    $this->fill((array) $response);
                } elseif ($user instanceof User && ! $this->hasRecipient($user->id)) {
                    // As Discord sends recipients: objects, not arrays.
                    $this->attributes['recipients'][] = (object) $user->getRawAttributes();
                }

                return $this;
            });
    }

    /**
     * Removes a user from the group DM.
     *
     * @link https://docs.discord.com/developers/resources/channel#group-dm-remove-recipient
     *
     * @param User|string $user The user to remove.
     *
     * @return PromiseInterface<self>
     *
     * @since 10.60.0
     */
    public function removeRecipient($user): PromiseInterface
    {
        $user_id = $user instanceof Part ? $user->id : (string) $user;

        return $this->http->delete(Endpoint::bind(Endpoint::CHANNEL_RECIPIENT, $this->id, $user_id))
            ->then(function () use ($user_id) {
                $this->attributes['recipients'] = array_values(array_filter(
                    $this->attributes['recipients'] ?? [],
                    static fn ($recipient): bool => (((array) $recipient)['id'] ?? null) !== $user_id,
                ));

                return $this;
            });
    }

    /**
     * Whether a user is among the group DM's recipients.
     */
    protected function hasRecipient(string $user_id): bool
    {
        foreach ($this->attributes['recipients'] ?? [] as $recipient) {
            if ((((array) $recipient)['id'] ?? null) === $user_id) {
                return true;
            }
        }

        return false;
    }
}
