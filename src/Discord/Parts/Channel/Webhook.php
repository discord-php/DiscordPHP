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

namespace Discord\Parts\Channel;

use Discord\Builders\MessageBuilder;
use Discord\Http\Endpoint;
use Discord\Http\Http;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use Discord\Repository\Channel\WebhookMessageRepository;
use React\Promise\PromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Webhooks are a low-effort way to post messages to channels in Discord. They
 * do not require a bot user or authentication to use.
 *
 * @link https://discord.com/developers/docs/resources/webhook#webhook-resource
 *
 * @since 5.0.0
 *
 * @property      string       $id             The id of the webhook.
 * @property      int          $type           The type of webhook.
 * @property      ?string|null $guild_id       The guild ID this webhook is for, if any.
 * @property-read Guild|null   $guild          The guild this webhook is for, if any.
 * @property      ?string|null $channel_id     The channel ID this webhook is for, if any.
 * @property-read Channel|null $channel        The channel this webhook is for, if any.
 * @property      User|null    $user           The user that created the webhook.
 * @property      ?string      $name           The name of the webhook.
 * @property      ?string      $avatar         The avatar of the webhook.
 * @property      string|null  $token          The token of the webhook.
 * @property      ?string      $application_id The bot/OAuth2 application that created this webhook.
 * @property      object|null  $source_guild   The partial guild of the channel that this webhook is following (returned for Channel Follower Webhooks).
 * @property      object|null  $source_channel The partial channel that this webhook is following (returned for Channel Follower Webhooks).
 * @property      string|null  $url            The url used for executing the webhook (returned by the webhooks OAuth2 flow).
 *
 * @property WebhookMessageRepository $messages
 */
class Webhook extends Part
{
    public const TYPE_INCOMING = 1;
    public const TYPE_CHANNEL_FOLLOWER = 2;
    public const TYPE_APPLICATION = 3;

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    protected $repositories = [
        'messages' => WebhookMessageRepository::class,
    ];

    /**
     * Executes the webhook with an array of data.
     *
     * @link https://discord.com/developers/docs/resources/webhook#execute-webhook
     *
     * @param MessageBuilder|array $data
     * @param array                $queryparams Query string params to add to the request.
     *
     * @return PromiseInterface<void|Message> Message returned if wait parameter is set true.
     */
    public function execute($data, array $queryparams = []): PromiseInterface
    {
        $endpoint = Endpoint::bind(Endpoint::WEBHOOK_EXECUTE, $this->id, $this->token);

        $resolver = new OptionsResolver();
        $resolver
            ->setDefined(['wait', 'thread_id'])
            ->setAllowedTypes('wait', 'bool')
            ->setAllowedTypes('thread_id', ['string', 'int']);

        $options = $resolver->resolve($queryparams);

        foreach ($options as $query => $param) {
            $endpoint->addQuery($query, $param);
        }

        if ($data instanceof MessageBuilder && $data->requiresMultipart()) {
            $multipart = $data->toMultipart();

            $promise = $this->http->post($endpoint, (string) $multipart, $multipart->getHeaders());
        } else {
            $promise = $this->http->post($endpoint, $data);
        }

        if (! empty($queryparams['wait'])) {
            return $promise->then(fn ($response) => $this->factory->part(Message::class, (array) $response + ['guild_id' => $this->guild_id], true));
        }

        return $promise;
    }

    /**
     * Edits a previously-sent webhook message from the same token.
     *
     * @link https://discord.com/developers/docs/resources/webhook#edit-webhook-message
     *
     * @param string         $message_id  ID of the message to update.
     * @param MessageBuilder $builder     The new message.
     * @param array          $queryparams Query string params to add to the request.
     *
     * @return PromiseInterface<Message>
     */
    public function updateMessage(string $message_id, MessageBuilder $builder, array $queryparams = []): PromiseInterface
    {
        $endpoint = Endpoint::bind(Endpoint::WEBHOOK_MESSAGE, $this->id, $this->token, $message_id);

        $resolver = new OptionsResolver();
        $resolver
            ->setDefined('thread_id')
            ->setAllowedTypes('thread_id', ['string', 'int']);

        $options = $resolver->resolve($queryparams);

        foreach ($options as $query => $param) {
            $endpoint->addQuery($query, $param);
        }

        if ($builder->requiresMultipart()) {
            $multipart = $builder->toMultipart();

            $promise = $this->http->patch($endpoint, (string) $multipart, $multipart->getHeaders());
        } else {
            $promise = $this->http->patch($endpoint, $builder);
        }

        return $promise->then(function ($response) {
            $channel = $this->channel;
            if (($channel && $message = $channel->messages->get('id', $response->id)) || $message = $this->messages->get('id', $response->id)) {
                $message->fill((array) $response);

                return $message;
            }

            return $this->factory->part(Message::class, (array) $response + ['guild_id' => $this->guild_id], true);
        });
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

        if ($guild = $this->guild) {
            if ($channel = $guild->channels->get('id', $this->channel_id)) {
                return $channel;
            }
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
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/webhook#create-webhook-json-params
     */
    public function getCreatableAttributes(): array
    {
        return [
            'name' => $this->name,
        ] + $this->makeOptionalAttributes([
            'avatar' => $this->avatar,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/webhook#modify-webhook-json-params
     */
    public function getUpdatableAttributes(): array
    {
        return $this->makeOptionalAttributes([
            'name' => $this->name,
            'channel_id' => $this->channel_id,
            'avatar' => $this->avatar,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        $attr = [
            'webhook_id' => $this->id,
        ];

        if (array_key_exists('token', $this->attributes)) {
            $attr['webhook_token'] = $this->token;
        }

        return $attr;
    }
}
