<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Discord\Http\Endpoint;
use Discord\Http\Http;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;

/**
 * Webhooks are a low-effort way to post messages to channels in Discord. They do not require a bot user or authentication to use.
 *
 * @see https://discord.com/developers/docs/resources/webhook#webhook-resource
 *
 * @property string       $id             The id of the webhook.
 * @property int          $type           The type of webhook.
 * @property string|null  $guild_id       The guild ID this is for, if any.
 * @property Guild|null   $guild          The guild this is for, if any.
 * @property string|null  $channel_id     The channel ID this is for, if any.
 * @property Channel|null $channel        The channel ID this is for, if any.
 * @property User|null    $user           The user that created the webhook.
 * @property string       $name           The name of the webhook.
 * @property string       $avatar         The avatar of the webhook.
 * @property string|null  $token          The token of the webhook.
 * @property object|null  $source_guild   The partial guild of the channel that this webhook is following (returned for Channel Follower Webhooks).
 * @property object|null  $source_channel The partial channel that this webhook is following (returned for Channel Follower Webhooks).
 * @property string|null  $url            The url used for executing the webhook (returned by the webhooks OAuth2 flow).
 */
class Webhook extends Part
{
    public const TYPE_INCOMING = 1;
    public const TYPE_CHANNEL_FOLLOWER = 2;
    public const TYPE_APPLICATION = 3;

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
        'type',
        'guild_id',
        'channel_id',
        'user',
        'name',
        'avatar',
        'token',
        'application_id',
        'source_guild',
        'source_channel',
        'url',
    ];

    /**
     * Executes the webhook with an array of data.
     *
     * @see https://discord.com/developers/docs/resources/webhook#execute-webhook-jsonform-params
     *
     * @param array $data
     *
     * @return PromiseInterface
     */
    public function execute(array $data): PromiseInterface
    {
        return $this->http->post(Endpoint::bind(Endpoint::WEBHOOK_EXECUTE, $this->id, $this->token), $data);
    }

    /**
     * Gets the guild the webhook belongs to.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the channel the webhook belongs to.
     *
     * @return Channel|null
     */
    protected function getChannelAttribute(): ?Channel
    {
        if (! isset($this->attributes['channel_id'])) {
            return null;
        }

        if ($this->guild && $channel = $this->guild->channels->get('id', $this->channel_id)) {
            return $channel;
        }

        return $this->discord->getChannel($this->channel_id);
    }

    /**
     * Gets the user that created the webhook.
     *
     * @return User|null
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
     * Gets the webhook url attribute.
     *
     * @return string|null
     */
    protected function getUrlAttribute(): ?string
    {
        if (isset($this->attributes['url'])) {
            return $this->attributes['url'];
        }

        if (isset($this->attributes['token'])) {
            return Http::BASE_URL.'/'.Endpoint::bind(Endpoint::WEBHOOK_TOKEN, $this->id, $this->token);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'avatar' => $this->avatar,
            'channel_id' => $this->channel_id,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'avatar' => $this->avatar,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'webhook_id' => $this->id,
        ];
    }
}
