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
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Http\Http;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use Discord\Repository\Channel\WebhookMessageRepository;
use Discord\Repository\Channel\WebhookRepository;
use React\Promise\PromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function React\Promise\reject;

/**
 * Webhooks are a low-effort way to post messages to channels in Discord. They do not require a bot user or authentication to use.
 *
 * Apps can also subscribe to webhook events (i.e. outgoing webhooks) when events happen in Discord, which is detailed in the Webhook Events documentation.
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
 * @property      Guild|null   $source_guild   The partial guild of the channel that this webhook is following (returned for Channel Follower Webhooks).
 * @property      Channel|null $source_channel The partial channel that this webhook is following (returned for Channel Follower Webhooks).
 * @property      string|null  $url            The url used for executing the webhook (returned by the webhooks OAuth2 flow).
 *
 * @property WebhookMessageRepository $messages
 */
class Webhook extends Part
{
    /** Incoming Webhooks can post messages to channels with a generated token. */
    public const TYPE_INCOMING = 1;
    /** Channel Follower Webhooks are internal webhooks used with Channel Following to post new messages into channels. */
    public const TYPE_CHANNEL_FOLLOWER = 2;
    /** Application webhooks are webhooks used with Interactions. */
    public const TYPE_APPLICATION = 3;

    /**
     * @inheritDoc
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
     * @inheritDoc
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
     * @param array                $queryparams                    Query string params to add to the request.
     * @param ?bool                $queryparams['wait']            Waits for server confirmation of message send before response, and returns the created message body (defaults to false; when false a message that is not saved does not return an error)
     * @param ?string|int          $queryparams['thread_id']       Send a message to the specified thread within a webhook's channel. The thread will automatically be unarchived.
     * @param ?bool                $queryparams['with_components'] Whether to respect the components field of the request. When enabled, allows application-owned webhooks to use all components and non-owned webhooks to use non-interactive components. (defaults to false)
     *
     * @return PromiseInterface<Message|void> Message returned if wait parameter is set true.
     */
    public function execute($data, array $queryparams = []): PromiseInterface
    {
        $endpoint = Endpoint::bind(Endpoint::WEBHOOK_EXECUTE, $this->id, $this->token);

        $resolver = new OptionsResolver();
        $resolver
            ->setDefined(['wait', 'thread_id', 'with_components'])
            ->setAllowedTypes('wait', 'bool')
            ->setAllowedTypes('thread_id', ['string', 'int'])
            ->setAllowedTypes('with_components', 'bool');

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
     * Executes a Slack-compatible webhook.
     *
     * Refer to Slack's documentation for more information. Discord does not support Slack's `channel`, `icon_emoji`, `mrkdwn`, or `mrkdwn_in` properties.
     *
     * @param array       $queryparams
     * @param ?string|int $queryparams['thread_id'] Id of the thread to send the message in.
     * @param ?bool       $queryparams['wait']      Waits for server confirmation of message send before response (defaults to `true`; when `false` a message that is not saved does not return an error)
     *
     * @since 10.27.0
     */
    public function executeSlack($data, array $queryparams = []): PromiseInterface
    {
        $endpoint = Endpoint::bind(Endpoint::WEBHOOK_EXECUTE_SLACK, $this->id, $this->token);

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
     * Executes a GitHub-compatible webhook.
     *
     * Add a new webhook to your GitHub repo (in the repo's settings), and use this endpoint as the "Payload URL."
     * You can choose what events your Discord channel receives by choosing the "Let me select individual events" option and selecting individual events for the new webhook you're configuring.
     * The supported events are `commit_comment`, `create`, `delete`, `fork`, `issue_comment`, `issues`, `member`, `public`, `pull_request`, `pull_request_review`, `pull_request_review_comment`, `push`, `release`, `watch`, `check_run`, `check_suite`, `discussion`, and `discussion_comment`.
     *
     * @param array       $queryparams
     * @param ?string|int $queryparams['thread_id'] Id of the thread to send the message in.
     * @param ?bool       $queryparams['wait']      Waits for server confirmation of message send before response (defaults to `true`; when `false` a message that is not saved does not return an error)
     *
     * @since 10.27.0
     */
    public function executeGitHub($data, array $queryparams = []): PromiseInterface
    {
        $endpoint = Endpoint::bind(Endpoint::WEBHOOK_EXECUTE_GITHUB, $this->id, $this->token);

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

        return $this->attributePartHelper('user', User::class);
    }

    /**
     * Gets the source guild attribute.
     *
     * @return Guild|null
     *
     * @since 10.23.0
     */
    protected function getSourceGuildAttribute(): ?Guild
    {
        return $this::attributePartHelper('source_guild', Guild::class);
    }

    /**
     * Gets the source channel attribute.
     *
     * @return Channel|null
     *
     * @since 10.23.0
     */
    protected function getSourceChannelAttribute(): ?Channel
    {
        return $this::attributePartHelper('source_channel', Channel::class);
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
     * @inheritDoc
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
     * @inheritDoc
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
     * Gets the originating repository of the part.
     *
     * @since 10.42.0
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return WebhookRepository|null The repository, or null if required part data is missing.
     */
    public function getRepository(): WebhookRepository|null
    {
        if (! isset($this->attributes['channel_id'])) {
            return null;
        }

        /** @var Channel */
        $channel = $this->channel ?? $this->factory->part(Channel::class, ['id' => $this->channel_id], true);

        return $channel->webhooks;
    }

    /**
     * @inheritDoc
     */
    public function save(?string $reason = null): PromiseInterface
    {
        if (! isset($this->attributes['channel_id'])) {
            return parent::save($reason);
        }

        /** @var Channel */
        $channel = $this->channel ?? $this->factory->part(Channel::class, ['id' => $this->channel_id], true);

        if ($botperms = $channel->getBotPermissions()) {
            if (! $botperms->manage_webhooks) {
                return reject(new NoPermissionsException("You do not have permission to manage webhooks in the channel {$channel->id}."));
            }
        }

        return $channel->webhooks->save($this, $reason);
    }

    /**
     * @inheritDoc
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
