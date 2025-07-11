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

namespace Discord\Parts\Interactions;

use Discord\Builders\Components\Component;
use Discord\Builders\Components\ComponentObject;
use Discord\Builders\MessageBuilder;
use Discord\Exceptions\AttachmentSizeException;
use Discord\Helpers\Collection;
use Discord\Helpers\Multipart;
use Discord\Http\Endpoint;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Command\Choice;
use Discord\Parts\Channel\Message\Component as RequestComponent;
use Discord\Parts\Interactions\Request\InteractionData;
use Discord\Parts\Part;
use Discord\Parts\Permissions\ChannelPermission;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\WebSockets\Event;
use React\EventLoop\TimerInterface;
use React\Promise\PromiseInterface;

use function Discord\poly_strlen;
use function React\Promise\reject;

/**
 * Represents an interaction from Discord.
 *
 * @link https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object
 *
 * @since 7.0.0
 *
 * @property      string                 $id                             ID of the interaction.
 * @property      string                 $application_id                 ID of the application the interaction is for.
 * @property      int                    $type                           Type of interaction.
 * @property      InteractionData|null   $data                           Data associated with the interaction.
 * @property-read Guild|null             $guild                          Guild the interaction was sent from.
 * @property      string|null            $guild_id                       ID of the guild the interaction was sent from.
 * @property-read Channel|null           $channel                        Channel the interaction was sent from.
 * @property      string|null            $channel_id                     ID of the channel the interaction was sent from.
 * @property      Member|null            $member                         Member who invoked the interaction.
 * @property      User|null              $user                           User who invoked the interaction.
 * @property      string                 $token                          Continuation token for responding to the interaction.
 * @property-read int                    $version                        Version of interaction.
 * @property      Message|null           $message                        Message that triggered the interactions, when triggered from message components.
 * @property-read ChannelPermission|null $app_permissions                Bitwise set of permissions the app or bot has within the channel the interaction was sent from.
 * @property      string|null            $locale                         The selected language of the invoking user.
 * @property      string|null            $guild_locale                   The guild's preferred locale, if invoked in a guild.
 * @property      array                  $entitlements                   For monetized apps, any entitlements for the invoking user, representing access to premium SKUs
 * @property      array                  $authorizing_integration_owners Mapping of installation contexts that the interaction was authorized for to related user or guild IDs.
 * @property      int|null               $context                        Context where the interaction was triggered from.
 * @property      int                    $attachment_size_limit          Attachment size limit in bytes.
 */
class Interaction extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'application_id',
        'type',
        'data',
        'guild',
        'guild_id',
        'channel',
        'channel_id',
        'member',
        'user',
        'token',
        'version',
        'message',
        'app_permissions',
        'locale',
        'guild_locale',
        'entitlements',
        'authorizing_integration_owners',
        'context',
        'attachment_size_limit',
    ];

    /**
     * Whether we have responded to the interaction yet.
     *
     * @var bool
     */
    protected $responded = false;

    public const TYPE_PING = 1;
    public const TYPE_APPLICATION_COMMAND = 2;
    public const TYPE_MESSAGE_COMPONENT = 3;
    public const TYPE_APPLICATION_COMMAND_AUTOCOMPLETE = 4;
    public const TYPE_MODAL_SUBMIT = 5;

    public const RESPONSE_TYPE_PONG = 1;
    public const RESPONSE_TYPE_CHANNEL_MESSAGE_WITH_SOURCE = 4;
    public const RESPONSE_TYPE_DEFERRED_CHANNEL_MESSAGE_WITH_SOURCE = 5;
    public const RESPONSE_TYPE_DEFERRED_UPDATE_MESSAGE = 6;
    public const RESPONSE_TYPE_UPDATE_MESSAGE = 7;
    public const RESPONSE_TYPE_APPLICATION_COMMAND_AUTOCOMPLETE_RESULT = 8;
    public const RESPONSE_TYPE_MODAL = 9;
    public const RESPONSE_TYPE_PREMIUM_REQUIRED = 10;
    public const RESPONSE_TYPE_LAUNCH_ACTIVITY = 12;

    public const CONTEXT_TYPE_GUILD = 0;
    public const CONTEXT_TYPE_BOT_DM = 1;
    public const CONTEXT_TYPE_PRIVATE_CHANNEL = 2;

    /**
     * Returns true if this interaction has been internally responded.
     *
     * @return bool The interaction is responded
     */
    public function isResponded(): bool
    {
        return $this->responded;
    }

    /**
     * Returns the data associated with the interaction.
     *
     * @return InteractionData|null
     */
    protected function getDataAttribute(): ?InteractionData
    {
        if (! isset($this->attributes['data'])) {
            return null;
        }

        $adata = $this->attributes['data'];
        if (! isset($adata->guild_id) && isset($this->attributes['guild_id'])) {
            $adata->guild_id = $this->guild_id;
        }

        return $this->createOf(InteractionData::class, $adata);
    }

    /**
     * Returns the guild the interaction was invoked from.
     *
     * @return Guild|null `null` when invoked via DM.
     */
    protected function getGuildAttribute(): ?Guild
    {
        if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
            return $guild;
        }

        if (isset($this->attributes['guild'])) {
            return $this->factory->part(Guild::class, (array) $this->attributes['guild'], true);
        }

        return null;
    }

    /**
     * Returns the channel the interaction was invoked from.
     *
     * @return Channel|Thread|null
     */
    protected function getChannelAttribute(): ?Part
    {
        $channelId = $this->attributes['channel']->id ?? $this->channel_id;
        if ($guild = $this->guild) {
            $channels = $guild->channels;
            if (
                ! in_array($this->attributes['channel']->type ?? null, [Channel::TYPE_PUBLIC_THREAD, CHANNEL::TYPE_PRIVATE_THREAD, CHANNEL::TYPE_ANNOUNCEMENT_THREAD])
                && $channel = $channels->get('id', $channelId)
            ) {
                return $channel;
            }
            foreach ($channels as $parent) {
                if ($thread = $parent->threads->get('id', $channelId)) {
                    return $thread;
                }
            }
        }

        if ($channel = $this->discord->getChannel($channelId)) {
            return $channel;
        }

        if (isset($this->attributes['channel'])) {
            return $this->factory->part(Channel::class, (array) $this->attributes['channel'], true);
        }

        return null;
    }

    /**
     * Returns the member who invoked the interaction.
     *
     * @return Member|null `null` when invoked via DM.
     */
    protected function getMemberAttribute(): ?Member
    {
        if (isset($this->attributes['member'])) {
            if ($guild = $this->guild) {
                if ($member = $guild->members->get('id', $this->attributes['member']->user->id)) {
                    // @todo Temporary workaround until member is cached from INTERACTION_CREATE event
                    $member->permissions = $this->attributes['member']->permissions;

                    return $member;
                }
            }

            return $this->factory->part(Member::class, (array) $this->attributes['member'] + ['guild_id' => $this->guild_id], true);
        }

        return null;
    }

    /**
     * Returns the user who invoked the interaction.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        if ($member = $this->member) {
            return $member->user;
        }

        if (! isset($this->attributes['user'])) {
            return null;
        }

        return $this->factory->part(User::class, (array) $this->attributes['user'], true);
    }

    /**
     * Returns the message that triggered the interaction, when triggered via message components.
     *
     * @return Message|null
     */
    protected function getMessageAttribute(): ?Message
    {
        if (! isset($this->attributes['message'])) {
            return null;
        }

        return $this->factory->part(Message::class, (array) $this->attributes['message'], true);
    }

    /**
     * Returns the permissions the app or bot has within the channel the interaction was sent from.
     *
     * @return ChannelPermission|null
     */
    protected function getAppPermissionsAttribute(): ?ChannelPermission
    {
        if (! isset($this->attributes['app_permissions'])) {
            return null;
        }

        return $this->factory->part(ChannelPermission::class, ['bitwise' => $this->attributes['app_permissions']], true);
    }

    /**
     * Acknowledges an interaction without returning a response.
     * Only valid for message component interactions.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#responding-to-an-interaction
     *
     * @throws \LogicException Interaction is not Message Component or Modal Submit.
     *
     * @return PromiseInterface
     */
    public function acknowledge(): PromiseInterface
    {
        if ($this->type == self::TYPE_APPLICATION_COMMAND) {
            return $this->acknowledgeWithResponse();
        }

        if (! in_array($this->type, [self::TYPE_MESSAGE_COMPONENT, self::TYPE_MODAL_SUBMIT])) {
            return reject(new \LogicException('You can only acknowledge message component or modal submit interactions.'));
        }

        return $this->respond([
            'type' => self::RESPONSE_TYPE_DEFERRED_UPDATE_MESSAGE,
        ]);
    }

    /**
     * Acknowledges an interaction, creating a placeholder response message
     * which can be edited later through the `updateOriginalResponse` function.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#responding-to-an-interaction
     *
     * @param bool $ephemeral Whether the acknowledge should be ephemeral.
     *
     * @throws \LogicException Interaction is not Application Command, Message Component, or Modal Submit.
     *
     * @return PromiseInterface
     */
    public function acknowledgeWithResponse(bool $ephemeral = false): PromiseInterface
    {
        if (! in_array($this->type, [self::TYPE_APPLICATION_COMMAND, self::TYPE_MESSAGE_COMPONENT, self::TYPE_MODAL_SUBMIT])) {
            return reject(new \LogicException('You can only acknowledge application command, message component, or modal submit interactions.'));
        }

        return $this->respond([
            'type' => self::RESPONSE_TYPE_DEFERRED_CHANNEL_MESSAGE_WITH_SOURCE,
            'data' => $ephemeral ? ['flags' => 64] : null,
        ]);
    }

    /**
     * Updates the message that the interaction was triggered from.
     * Only valid for message component interactions.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#responding-to-an-interaction
     *
     * @param MessageBuilder $builder The new message content.
     *
     * @throws \LogicException Interaction is not Message Component.
     *
     * @return PromiseInterface
     */
    public function updateMessage(MessageBuilder $builder): PromiseInterface
    {
        if (! in_array($this->type, [self::TYPE_MESSAGE_COMPONENT, self::TYPE_MODAL_SUBMIT])) {
            return reject(new \LogicException('You can only update messages that occur due to a message component interaction.'));
        }

        if ($this->hasAttachmentsExceedingLimit($builder)) {
            return reject(new AttachmentSizeException());
        }

        return $this->respond([
            'type' => self::RESPONSE_TYPE_UPDATE_MESSAGE,
            'data' => $builder,
        ], $builder->requiresMultipart() ? $builder->toMultipart(false) : null);
    }

    /**
     * Retrieves the original interaction response.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#get-original-interaction-response
     *
     * @throws \RuntimeException Interaction is not created yet.
     *
     * @return PromiseInterface<Message>
     */
    public function getOriginalResponse(): PromiseInterface
    {
        if (! $this->created) {
            return reject(new \RuntimeException('Interaction has not been created yet.'));
        }

        return $this->http->get(Endpoint::bind(Endpoint::ORIGINAL_INTERACTION_RESPONSE, $this->application_id, $this->token))
            ->then(function ($response) {
                $this->responded = true;

                return $this->factory->part(Message::class, (array) $response, true);
            });
    }

    /**
     * Updates the original interaction response.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#edit-original-interaction-response
     *
     * @param MessageBuilder $builder New message contents.
     *
     * @throws \RuntimeException Interaction is not responded yet.
     *
     * @return PromiseInterface<Message>
     */
    public function updateOriginalResponse(MessageBuilder $builder): PromiseInterface
    {
        if (! $this->responded) {
            return reject(new \RuntimeException('Interaction has not been responded to.'));
        }

        if ($this->hasAttachmentsExceedingLimit($builder)) {
            return reject(new AttachmentSizeException());
        }

        return (function () use ($builder): PromiseInterface {
            if ($builder->requiresMultipart()) {
                $multipart = $builder->toMultipart();

                return $this->http->patch(Endpoint::bind(Endpoint::ORIGINAL_INTERACTION_RESPONSE, $this->application_id, $this->token), (string) $multipart, $multipart->getHeaders());
            }

            return $this->http->patch(Endpoint::bind(Endpoint::ORIGINAL_INTERACTION_RESPONSE, $this->application_id, $this->token), $builder);
        })()->then(fn ($response) => $this->factory->part(Message::class, (array) $response, true));
    }

    /**
     * Deletes the original interaction response.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#delete-original-interaction-response
     *
     * @throws \RuntimeException Interaction is not responded yet.
     *
     * @return PromiseInterface
     */
    public function deleteOriginalResponse(): PromiseInterface
    {
        if (! $this->responded) {
            return reject(new \RuntimeException('Interaction has not been responded to.'));
        }

        return $this->http->delete(Endpoint::bind(Endpoint::ORIGINAL_INTERACTION_RESPONSE, $this->application_id, $this->token));
    }

    /**
     * Sends a follow-up message to the interaction.
     *
     * Apps are limited to 5 followup messages per interaction if it was initiated from a user-installed app and isn't installed in the server (meaning the authorizing integration owners object only contains USER_INSTALL)
     *
     * When using this endpoint directly after responding to an interaction with `acknowledgeWithResponse()`,
     * this endpoint will function as Edit Original Interaction Response for backwards compatibility.
     * In this case, no new message will be created, and the loading message will be edited instead.
     * The ephemeral flag will be ignored, and the value you provided in the initial defer response will be preserved,
     * as an existing message's ephemeral state cannot be changed.
     * This behavior is deprecated, and you should use the Edit Original Interaction Response endpoint in this case instead.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#create-followup-message
     *
     * @param MessageBuilder $builder   Message to send.
     * @param bool           $ephemeral Whether the created follow-up should be ephemeral
     *
     * @throws \RuntimeException Interaction is not responded yet.
     *
     * @return PromiseInterface<Message>
     */
    public function sendFollowUpMessage(MessageBuilder $builder, bool $ephemeral = false): PromiseInterface
    {
        if (! $this->responded && $this->type != self::TYPE_MESSAGE_COMPONENT) {
            return reject(new \RuntimeException('Cannot create a follow-up message as the interaction has not been responded to.'));
        }

        if ($this->hasAttachmentsExceedingLimit($builder)) {
            return reject(new AttachmentSizeException());
        }

        if ($ephemeral) {
            $builder->setFlags($builder->getFlags() | Message::FLAG_EPHEMERAL);
        }

        return (function () use ($builder): PromiseInterface {
            if ($builder->requiresMultipart()) {
                $multipart = $builder->toMultipart();

                return $this->http->post(Endpoint::bind(Endpoint::CREATE_INTERACTION_FOLLOW_UP, $this->application_id, $this->token), (string) $multipart, $multipart->getHeaders());
            }

            return $this->http->post(Endpoint::bind(Endpoint::CREATE_INTERACTION_FOLLOW_UP, $this->application_id, $this->token), $builder);
        })()->then(fn ($response) => $this->factory->part(Message::class, (array) $response, true));
    }

    /**
     * Responds to the interaction with a message.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#create-interaction-response
     *
     * @param MessageBuilder $builder   Message to respond with.
     * @param bool           $ephemeral Whether the created message should be ephemeral.
     *
     * @throws \LogicException Interaction is not Application Command, Message Component, or Modal Submit.
     *
     * @return PromiseInterface
     */
    public function respondWithMessage(MessageBuilder|string $builder, bool $ephemeral = false): PromiseInterface
    {
        if (! in_array($this->type, [self::TYPE_APPLICATION_COMMAND, self::TYPE_MESSAGE_COMPONENT, self::TYPE_MODAL_SUBMIT])) {
            return reject(new \LogicException('You can only acknowledge application command, message component, or modal submit interactions.'));
        }

        if (is_string($builder)) {
            $builder = MessageBuilder::new()->setContent($builder);
        }

        if ($this->hasAttachmentsExceedingLimit($builder)) {
            return reject(new AttachmentSizeException());
        }

        if ($ephemeral) {
            $builder->setFlags($builder->getFlags() | Message::FLAG_EPHEMERAL);
        }

        return $this->respond([
            'type' => self::RESPONSE_TYPE_CHANNEL_MESSAGE_WITH_SOURCE,
            'data' => $builder,
        ], $builder->requiresMultipart() ? $builder->toMultipart(false) : null);
    }

    /**
     * Responds to the interaction with a payload.
     *
     * This is a separate function so that it can be overloaded when responding
     * via webhook.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#create-interaction-response
     *
     * @param array          $payload   Response payload.
     * @param Multipart|null $multipart Optional multipart payload.
     *
     * @throws \RuntimeException Interaction is already responded.
     *
     * @return PromiseInterface
     */
    protected function respond(array $payload, ?Multipart $multipart = null): PromiseInterface
    {
        if ($this->responded) {
            return reject(new \RuntimeException('Interaction has already been responded to.'));
        }

        $this->responded = true;

        if ($multipart) {
            $multipart->add([
                'name' => 'payload_json',
                'content' => json_encode($payload),
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            return $this->http->post(Endpoint::bind(Endpoint::INTERACTION_RESPONSE, $this->id, $this->token), (string) $multipart, $multipart->getHeaders());
        }

        return $this->http->post(Endpoint::bind(Endpoint::INTERACTION_RESPONSE, $this->id, $this->token), $payload);
    }

    /**
     * Updates a non ephemeral follow up message.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#edit-followup-message
     *
     * @param string         $message_id Message to update.
     * @param MessageBuilder $builder    New message contents.
     *
     * @throws \RuntimeException Interaction is not responded yet.
     *
     * @return PromiseInterface<Message>
     */
    public function updateFollowUpMessage(string $message_id, MessageBuilder $builder): PromiseInterface
    {
        if (! $this->responded) {
            return reject(new \RuntimeException('Cannot create a follow-up message as the interaction has not been responded to.'));
        }

        if ($this->hasAttachmentsExceedingLimit($builder)) {
            return reject(new AttachmentSizeException());
        }

        return (function () use ($message_id, $builder): PromiseInterface {
            if ($builder->requiresMultipart()) {
                $multipart = $builder->toMultipart();

                return $this->http->patch(Endpoint::bind(Endpoint::INTERACTION_FOLLOW_UP, $this->application_id, $this->token, $message_id), (string) $multipart, $multipart->getHeaders());
            }

            return $this->http->patch(Endpoint::bind(Endpoint::INTERACTION_FOLLOW_UP, $this->application_id, $this->token, $message_id), $builder);
        })()->then(fn ($response) => $this->factory->part(Message::class, (array) $response, true));
    }

    /**
     * Retrieves a non ephemeral follow up message.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#get-followup-message
     *
     * @param string $message_id Message to get.
     *
     * @throws \RuntimeException Interaction is not created yet.
     *
     * @return PromiseInterface<Message>
     */
    public function getFollowUpMessage(string $message_id): PromiseInterface
    {
        if (! $this->created) {
            return reject(new \RuntimeException('Interaction has not been created yet.'));
        }

        return $this->http->get(Endpoint::bind(Endpoint::INTERACTION_FOLLOW_UP, $this->application_id, $this->token, $message_id))
            ->then(function ($response) {
                $this->responded = true;

                return $this->factory->part(Message::class, (array) $response, true);
            });
    }

    /**
     * Deletes a follow up message.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#delete-followup-message
     *
     * @param string $message_id Message to delete.
     *
     * @throws \RuntimeException Interaction is not responded yet.
     *
     * @return PromiseInterface
     */
    public function deleteFollowUpMessage(string $message_id): PromiseInterface
    {
        if (! $this->responded) {
            return reject(new \RuntimeException('Interaction has not been responded to.'));
        }

        return $this->http->delete(Endpoint::bind(Endpoint::INTERACTION_FOLLOW_UP, $this->application_id, $this->token, $message_id));
    }

    /**
     * Responds to the interaction with auto complete suggestions.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#responding-to-an-interaction
     *
     * @param array|Choice[] $choices Autocomplete choices (max of 25 choices)
     *
     * @throws \LogicException Interaction is not Autocomplete.
     *
     * @return PromiseInterface
     */
    public function autoCompleteResult(array $choices): PromiseInterface
    {
        if ($this->type != self::TYPE_APPLICATION_COMMAND_AUTOCOMPLETE) {
            return reject(new \LogicException('You can only respond command option results with auto complete interactions.'));
        }

        return $this->respond([
            'type' => self::RESPONSE_TYPE_APPLICATION_COMMAND_AUTOCOMPLETE_RESULT,
            'data' => ['choices' => $choices],
        ]);
    }

    /**
     * Responds to the interaction with a popup modal.
     *
     * @link https://discord.com/developers/docs/interactions/receiving-and-responding#responding-to-an-interaction
     *
     * @param string                  $title      The title of the popup modal, max 45 characters
     * @param string                  $custom_id  Developer-defined identifier for the component, max 100 characters
     * @param array|ComponentObject[] $components Between 1 and 5 (inclusive) components that make up the modal contained in Action Row
     * @param callable|null           $submit     The function to call once modal is submitted.
     *
     * @throws \LogicException  Interaction is Ping or Modal Submit.
     * @throws \LengthException Modal title is longer than 45 characters.
     *
     * @return PromiseInterface
     */
    public function showModal(string $title, string $custom_id, array $components, ?callable $submit = null): PromiseInterface
    {
        if (in_array($this->type, [self::TYPE_PING, self::TYPE_MODAL_SUBMIT])) {
            return reject(new \LogicException('You cannot pop up a modal from a ping or modal submit interaction.'));
        }

        if (poly_strlen($title) > 45) {
            return reject(new \LengthException('Modal title must be less than or equal to 45 characters.'));
        }

        return $this->respond([
            'type' => self::RESPONSE_TYPE_MODAL,
            'data' => [
                'title' => $title,
                'custom_id' => $custom_id,
                'components' => $components,
            ],
        ])->then(function ($response) use ($custom_id, $submit) {
            if ($submit) {
                $listener = $this->createListener($custom_id, $submit, 60 * 15);
                $this->discord->on(Event::INTERACTION_CREATE, $listener);
            }

            return $response;
        });
    }

    /**
     * Creates a listener callback for handling modal submit interactions with a specific custom ID.
     *
     * @param string         $custom_id The custom ID to match against the interaction's custom_id.
     * @param callable       $submit    The callback to execute when the interaction matches. Receives the interaction and a collection of components.
     * @param int|float|null $timeout   Optional timeout in seconds after which the listener will be removed. (Mandatory for modal submit interactions)
     *
     * @return callable The listener callback to be registered for interaction events.
     */
    protected function createListener(string $custom_id, callable $submit, int|float|null $timeout = null): callable
    {
        $timer = null;

        $listener = function (Interaction $interaction) use ($custom_id, $submit, &$listener, &$timer) {
            if ($interaction->type == self::TYPE_MODAL_SUBMIT && $interaction->data->custom_id == $custom_id) {
                $components = Collection::for(RequestComponent::class, 'custom_id');
                foreach ($interaction->data->components as $actionrow) {
                    if ($actionrow->type == Component::TYPE_ACTION_ROW) {
                        foreach ($actionrow->components as $component) {
                            $components->pushItem($component);
                        }
                    }
                }
                $submit($interaction, $components);
                $this->discord->removeListener(Event::INTERACTION_CREATE, $listener);

                /** @var ?TimerInterface $timer */
                if ($timer !== null) {
                    $this->discord->getLoop()->cancelTimer($timer);
                }
            }
        };

        if ($timeout) {
            $timer = $this->discord->getLoop()->addTimer($timeout, fn () => $this->discord->removeListener(Event::INTERACTION_CREATE, $listener));
        }

        return $listener;
    }

    /**
     * Checks if any attachments in the MessageBuilder exceed the attachment size limit.
     *
     * @param MessageBuilder $builder The MessageBuilder instance to check.
     *
     * @return bool
     */
    protected function hasAttachmentsExceedingLimit(MessageBuilder $builder): bool
    {
        $attachments = $builder->getAttachments();
        foreach ($attachments as $attachment) {
            if ($attachment->size > $this->attachment_size_limit) {
                return true;
            }
        }

        return false;
    }
}
