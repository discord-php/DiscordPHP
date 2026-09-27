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

namespace Discord\Repository;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\GameDirectMessage;
use Discord\Parts\User\User;
use Discord\Http\Endpoint;
use React\Promise\PromiseInterface;

/**
 * Contains private channels and groups that the client has access to.
 *
 * @see Channel
 *
 * @since 4.0.0
 *
 * @method Channel|null get(string $discrim, $key)
 * @method Channel|null pull(string|int $key, $default = null)
 * @method Channel|null first()
 * @method Channel|null last()
 * @method Channel|null find(callable $callback)
 */
class PrivateChannelRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'get' => Endpoint::CHANNEL,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Channel::class;

    /**
     * Fires a Channel Update Gateway event.
     *
     * @link https://docs.discord.com/developers/resources/channel#modify-channel-json-params-group-dm
     *
     * @param Channel|string $channel
     * @param array          $params
     * @param string         $params['name'] 1-100 character channel name.
     * @param string         $icon['icon']   Base64 encoded icon.
     *
     * @return PromiseInterface
     *
     * @since 10.40.0
     */
    public function modifyGroupDM($channel, array $params = []): PromiseInterface
    {
        if (! is_string($channel)) {
            $channel = $channel->id;
        }

        $allowed = ['name', 'icon'];
        $params = array_filter(
            $params,
            fn ($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($params)) {
            throw new \InvalidArgumentException('No valid parameters to update.');
        }

        return $this->http->patch(EndPoint::bind(Endpoint::CHANNEL, $channel), $params);
    }

    /**
     * Sets the moderation metadata on a direct message sent during a Social SDK session, which is delivered to the players' clients.
     *
     * @link https://docs.discord.com/developers/discord-social-sdk/how-to/integrate-moderation#applying-moderation-decisions
     *
     * @param User|string              $user1    One user in the DM, or their id.
     * @param User|string              $user2    The other user, or their id; the order does not matter.
     * @param GameDirectMessage|string $message  The message or its id.
     * @param array                    $metadata Up to 5 free-form string key/value pairs describing the decision, e.g. `['action' => 'hide', 'reason' => 'toxicity']`.
     *
     * @return PromiseInterface
     *
     * @since 10.59.0
     */
    public function updateGameDirectMessageModerationMetadata($user1, $user2, $message, array $metadata): PromiseInterface
    {
        [$user1, $user2, $message] = array_map(static fn ($part) => is_string($part) ? $part : $part->id, [$user1, $user2, $message]);

        return $this->http->put(Endpoint::bind(Endpoint::PARTNER_SDK_DMS_MESSAGE_MODERATION_METADATA, $user1, $user2, $message), $metadata);
    }
}
