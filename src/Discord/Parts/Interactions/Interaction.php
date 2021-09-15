<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions;

use Discord\Builders\MessageBuilder;
use Discord\Helpers\Multipart;
use Discord\Http\Endpoint;
use Discord\InteractionResponseType;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Request\InteractionData;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use InvalidArgumentException;
use React\Promise\ExtendedPromiseInterface;
use RuntimeException;

/**
 * Represents an interaction from Discord.
 *
 * @property string               $id             ID of the interaction.
 * @property string               $application_id ID of the application the interaction is for.
 * @property int                  $type           Type of interaction.
 * @property InteractionData|null $data           Data associated with the interaction.
 * @property string|null          $guild_id       ID of the guild the interaction was sent from.
 * @property Guild|null           $guild          Guild the interaction was sent from.
 * @property string|null          $channel_id     ID of the channel the interaction was sent from.
 * @property Channel|null         $channel        Channel the interaction was sent from.
 * @property Member|null          $member         Member who invoked the interaction.
 * @property User                 $user           User who invoked the interaction.
 * @property string               $token          Continuation token for responding to the interaction.
 * @property int                  $version        Version of interaction.
 * @property Message|null         $message        Message that triggered the interactions, when triggered from message components.
 */
class Interaction extends Part
{
    public const TYPE_PING = 1;
    public const TYPE_APPLICATION_COMMAND = 2;
    public const TYPE_MESSAGE_COMPONENT = 3;

    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'application_id', 'type', 'data', 'guild_id', 'channel_id', 'member', 'user', 'token', 'version', 'message'];

    /**
     * @inheritdoc
     */
    protected $visible = ['guild', 'channel'];

    /**
     * Whether we have responded to the interaction yet.
     *
     * @var bool
     */
    protected $responded = false;

    /**
     * Returns the data associated with the interaction.
     *ean.
     * @return InteractionData|null
     */
    protected function getDataAttribute(): ?InteractionData
    {
        if (! isset($this->attributes['data'])) {
            return null;
        }

        return $this->factory->create(InteractionData::class, $this->attributes['data'], true);
    }

    /**
     * Returns the guild the interaction was invoked from. Null when invoked via DM.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the channel the interaction was invoked from.
     *
     * @return Channel|null
     */
    protected function getChannelAttribute(): ?Channel
    {
        if ($this->guild && $channel = $this->guild->channels->get('id', $this->channel_id)) {
            return $channel;
        }

        return $this->discord->getChannel($this->channel_id);
    }

    /**
     * Returns the member who invoked the interaction. Null when invoked via DM.
     *
     * @return Member|null
     */
    protected function getMemberAttribute(): ?Member
    {
        if (isset($this->attributes['member'])) {
            if ($this->guild && $member = $this->guild->members->get('id', $this->attributes['member']->user->id)) {
                return $member;
            }

            return $this->factory->create(Member::class, (array) $this->attributes['member'] + ['guild_id' => $this->guild_id], true);
        }

        return null;
    }

    /**
     * Returns the user who invoked the interaction.
     *
     * @return User
     */
    protected function getUserAttribute(): User
    {
        if ($this->member) {
            return $this->member->user;
        }

        return $this->factory->create(User::class, $this->attributes['user'], true);
    }

    /**
     * Returns the message that triggered the interaction, when triggered via message components.
     *
     * @return Message|null
     */
    protected function getMessageAttribute(): ?Message
    {
        if (isset($this->attributes['message'])) {
            return $this->factory->create(Message::class, $this->attributes['message'], true);
        }

        return null;
    }

    /**
     * Acknowledges an interaction without returning a response.
     * Only valid for message component interactions.
     *
     * @return ExtendedPromiseInterface
     */
    public function acknowledge(): ExtendedPromiseInterface
    {
        if ($this->type == Interaction::TYPE_APPLICATION_COMMAND) {
            return $this->acknowledgeWithResponse();
        }

        if ($this->type != Interaction::TYPE_MESSAGE_COMPONENT) {
            throw new InvalidArgumentException('You can only acknowledge message component interactions.');
        }

        return $this->respond([
            'type' => InteractionResponseType::DEFERRED_UPDATE_MESSAGE,
        ]);
    }

    /**
     * Acknowledges an interaction, creating a placeholder response message which can be edited later
     * through the `updateOriginalResponse` function.
     *
     * @return ExtendedPromiseInterface
     */
    public function acknowledgeWithResponse(): ExtendedPromiseInterface
    {
        return $this->respond([
            'type' => InteractionResponseType::DEFERRED_CHANNEL_MESSAGE_WITH_SOURCE,
        ]);
    }

    /**
     * Updates the message that the interaction was triggered from.
     * Only valid for message component interactions.
     *
     * @param MessageBuilder $builder The new message content.
     *
     * @return ExtendedPromiseInterface
     */
    public function updateMessage(MessageBuilder $builder): ExtendedPromiseInterface
    {
        if ($this->type != Interaction::TYPE_MESSAGE_COMPONENT) {
            throw new InvalidArgumentException('You can only update messages that occur due to a message component interaction.');
        }

        return $this->respond([
            'type' => InteractionResponseType::UPDATE_MESSAGE,
            'data' => $builder,
        ], $builder->requiresMultipart() ? $builder->toMultipart(false) : null);
    }

    /**
     * Retrieves the original interaction response.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function getOriginalResponse(): ExtendedPromiseInterface
    {
        if (! $this->responded) {
            throw new RuntimeException('Interaction has not been responded to.');
        }

        return $this->http->get(Endpoint::bind(Endpoint::ORIGINAL_INTERACTION_RESPONSE, $this->application_id, $this->token))
            ->then(function ($response) {
                return $this->factory->create(Message::class, $response, true);
            });
    }

    /**
     * Updates the original interaction response.
     *
     * @param MessageBuilder $builder New message contents.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function updateOriginalResponse(MessageBuilder $builder): ExtendedPromiseInterface
    {
        if (! $this->responded) {
            throw new RuntimeException('Interaction has not been responded to.');
        }

        return (function () use ($builder): ExtendedPromiseInterface {
            if ($builder->requiresMultipart()) {
                $multipart = $builder->toMultipart();

                return $this->http->patch(Endpoint::bind(Endpoint::ORIGINAL_INTERACTION_RESPONSE, $this->application_id, $this->token), (string) $multipart, $multipart->getHeaders());
            }

            return $this->http->patch(Endpoint::bind(Endpoint::ORIGINAL_INTERACTION_RESPONSE, $this->application_id, $this->token), $builder);
        })()->then(function ($response) {
            return $this->factory->create(Message::class, $response, true);
        });
    }

    /**
     * Deletes the original interaction response.
     *
     * @return ExtendedPromiseInterface
     */
    public function deleteOriginalResponse(): ExtendedPromiseInterface
    {
        if (! $this->responded) {
            throw new RuntimeException('Interaction has not been responded to.');
        }

        return $this->http->delete(Endpoint::bind(Endpoint::ORIGINAL_INTERACTION_RESPONSE, $this->application_id, $this->token));
    }

    /**
     * Sends a follow-up message to the interaction.
     *
     * @param MessageBuilder $builder   Message to send.
     * @param bool           $ephemeral Whether the created follow-up should be ephemeral.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function sendFollowUpMessage(MessageBuilder $builder, bool $ephemeral = false): ExtendedPromiseInterface
    {
        if (! $this->responded) {
            throw new RuntimeException('Cannot create a follow-up message as the interaction has not been responded to.');
        }

        if ($ephemeral) {
            $builder->_setFlags(64);
        }

        return (function () use ($builder): ExtendedPromiseInterface {
            if ($builder->requiresMultipart()) {
                $multipart = $builder->toMultipart();

                return $this->http->post(Endpoint::bind(Endpoint::CREATE_INTERACTION_FOLLOW_UP, $this->application_id, $this->token), (string) $multipart, $multipart->getHeaders());
            }

            return $this->http->post(Endpoint::bind(Endpoint::CREATE_INTERACTION_FOLLOW_UP, $this->application_id, $this->token), $builder);
        })()->then(function ($response) {
            return $this->factory->create(Message::class, $response, true);
        });
    }

    /**
     * Responds to the interaction with a message.
     *
     * @param MessageBuilder $builder   Message to respond with.
     * @param bool           $ephemeral Whether the created message should be ephemeral.
     *
     * @return ExtendedPromiseInterface
     */
    public function respondWithMessage(MessageBuilder $builder, bool $ephemeral = false): ExtendedPromiseInterface
    {
        if ($ephemeral) {
            $builder->_setFlags(64);
        }

        return $this->respond([
            'type' => InteractionResponseType::CHANNEL_MESSAGE_WITH_SOURCE,
            'data' => $builder,
        ], $builder->requiresMultipart() ? $builder->toMultipart(false) : null);
    }

    /**
     * Responds to the interaction with a payload.
     *
     * This is a seperate function so that it can be overloaded when responding via
     * webhook.
     *
     * @param array          $payload   Response payload.
     * @param Multipart|null $multipart Optional multipart payload.
     *
     * @return ExtendedPromiseInterface
     */
    protected function respond(array $payload, ?Multipart $multipart = null): ExtendedPromiseInterface
    {
        if ($this->responded) {
            throw new RuntimeException('Interaction has already been responded to.');
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
     * Replies to the interaction with a message.
     * Backported for DiscordPHP-Slash, equal to respondWithMessage
     * @see respondWithMessage()
     *
     * @see https://discord.com/developers/docs/interactions/slash-commands#interaction-response-interactionapplicationcommandcallbackdata
     *
     * @param string               $content          String content for the message. Required.
     * @param bool                 $tts              Whether the message should be text-to-speech.
     * @param array[]|Embed[]|null $embeds           An array of up to 10 embeds. Can also be an array of DiscordPHP embeds.
     * @param array|null           $allowed_mentions Allowed mentions object. See Discord developer docs.
     * @param int|null             $flags            Set to 64 to make your response ephemeral
     *
     * Source is unused
     * @see https://discord.com/developers/docs/change-log#changes-to-slash-command-response-types-and-flags
     */
    public function reply(string $content, bool $tts = false, ?array $embeds = [], ?array $allowed_mentions = null, ?bool $source = false, ?int $flags = null)
    {
        $builder = MessageBuilder::new()
            ->setContent($content)
            ->setTts($tts);

        if ($embeds) {
            $embeds = array_map(function ($e) {
                if ($e instanceof Embed) {
                    return $e->getRawAttributes();
                }
    
                return $e;
            }, $embeds);
            $builder->setEmbeds($embeds);
        }

        if ($allowed_mentions) {
            $builder->setAllowedMentions($allowed_mentions);
        }

        if ($flags) {
            $builder->_setFlags($flags);
        }

        return $this->respondWithMessage($builder);
    }

    /**
     * Replies to the interaction with a message and shows the source message.
     * Alias for `reply()` with source = true.
     *
     * @deprecated 7.1.0 Backported for DiscordPHP-Slash
     * @see respondWithMessage()
     *
     * @param string     $content
     * @param bool       $tts
     * @param array|null $embeds
     * @param array|null $allowed_mentions
     * @param int|null   $flags
     */
    public function replyWithSource(string $content, bool $tts = false, ?array $embeds = null, ?array $allowed_mentions = null, ?int $flags = null)
    {
        if ($this->type != Interaction::TYPE_APPLICATION_COMMAND) {
            throw new InvalidArgumentException('You can only reply messages that occur due to a application command interaction. Use respondWithMessage() for message component');
        }

        $this->reply($content, $tts, $embeds, $allowed_mentions, true, $flags);
    }

     /**
     * Updates the original response to the interaction.
     * Must have already used `reply` or `replyWithSource`.
     * @deprecated 7.1.0 Backported for DiscordPHP-Slash
     * @see updateOriginalResponse()
     *
     * @param string               $content          Content of the message.
     * @param array[]|Embed[]|null $embeds           An array of up to 10 embeds. Can also be an array of DiscordPHP embeds.
     * @param array|null           $allowed_mentions Allowed mentions object. See Discord developer docs.
     *
     * @return ExtendedPromiseInterface
     */
    public function updateInitialResponse(?string $content = null, ?array $embeds = null, ?array $allowed_mentions = null): ExtendedPromiseInterface
    {
        $builder = MessageBuilder::new()
            ->setContent($content);

        if ($embeds) {
            $embeds = array_map(function ($e) {
                if ($e instanceof Embed) {
                    return $e->getRawAttributes();
                }
    
                return $e;
            }, $embeds);
            $builder->setEmbeds($embeds);
        }

        if ($allowed_mentions) {
            $builder->setAllowedMentions($allowed_mentions);
        }

        return $this->updateOriginalResponse($builder);
    }

    /**
     * Deletes the original response to the interaction.
     * Must have already used `reply` or `replyToSource`.
     * @deprecated 7.1.0 Backported for DiscordPHP-Slash
     * @see deleteOriginalResponse()
     *
     * @return ExtendedPromiseInterface
     */
    public function deleteInitialResponse(): ExtendedPromiseInterface
    {
        return $this->deleteOriginalResponse();
    }

    /**
     * Updates a follow up message.
     * Backported for DiscordPHP-Slash
     *
     * @param string               $message_id
     * @param string|null          $content
     * @param array[]|Embed[]|null $embeds
     * @param array|null           $allowed_mentions
     *
     * @return ExtendedPromiseInterface
     */
    public function updateFollowUpMessage(string $message_id, string $content = null, array $embeds = null, array $allowed_mentions = null)
    {
        if ($this->type != Interaction::TYPE_APPLICATION_COMMAND) {
            throw new InvalidArgumentException('You can only update follow up messages that occur due to a application command interaction.');
        }

        if (! $this->responded) {
            throw new RuntimeException('Cannot create a follow-up message as the interaction has not been responded to.');
        }

        return (function () use ($message_id, $content, $embeds, $allowed_mentions): ExtendedPromiseInterface {
            return $this->http->patch(Endpoint::bind(Endpoint::INTERACTION_FOLLOW_UP, $this->application_id, $this->token, $message_id), [
                'content' => $content,
                'embeds' => $embeds,
                'allowed_mentions' => $allowed_mentions,
            ]);
        })()->then(function ($response) {
            return $this->factory->create(Message::class, $response, true);
        });
    }
}
