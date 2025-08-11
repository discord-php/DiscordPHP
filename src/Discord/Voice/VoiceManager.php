<?php

declare(strict_types=1);

namespace Discord\Voice;

use Discord\Discord;
use Discord\Exceptions\Voice\CantJoinMoreThanOneChannelException;
use Discord\Exceptions\Voice\CantSpeakInChannelException;
use Discord\Exceptions\Voice\ChannelMustAllowVoiceException;
use Discord\Exceptions\Voice\ClientMustAllowVoiceException;
use Discord\Exceptions\Voice\EnterChannelDeniedException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\WebSockets\VoiceServerUpdate;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Event;
use Discord\WebSockets\Op;
use Discord\WebSockets\VoicePayload;
use Evenement\EventEmitterTrait;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Manages voice clients for the Discord bot.
 *
 * @requires libopus - Linux | NOT TESTED - WINDOWS
 * @requires FFMPEG - Linux | NOT TESTED - WINDOWS
 *
 * @since 10.19.0
 */
final class VoiceManager
{
    use EventEmitterTrait;

    /**
     * @param Discord $bot
     * @param array<string, VoiceClient> $clients
     */
    public function __construct(
        protected Discord $bot,
        public array $clients = [],
    ) {
    }

    /**
     * Handles the creation of a new voice client and joins the specified channel.
     *
     * @param \Discord\Parts\Channel\Channel $channel
     * @param \Discord\Discord $discord
     * @param bool $mute
     * @param bool $deaf
     *
     * @throws \Discord\Exceptions\Voice\ChannelMustAllowVoiceException
     * @throws \Discord\Exceptions\Voice\EnterChannelDeniedException
     * @throws \Discord\Exceptions\Voice\CantJoinMoreThanOneChannelException
     * @throws \Discord\Exceptions\Voice\CantSpeakInChannelException
     *
     * @return \React\Promise\PromiseInterface
     */
    public function joinChannel(Channel $channel, Discord $discord, bool $mute = false, bool $deaf = true): PromiseInterface
    {
        $deferred = new Deferred();

        try {
            if (! $channel->isVoiceBased()) {
                throw new ChannelMustAllowVoiceException();
            }

            if (! $channel->canJoin()) {
                throw new EnterChannelDeniedException();
            }

            if (! $channel->canSpeak() && ! $mute) {
                throw new CantSpeakInChannelException();
            }

            // TODO: Make this an option for the user instead of being forced
            if (isset($this->clients[$channel->guild_id])) {
                throw new CantJoinMoreThanOneChannelException();
            }
        } catch (\Throwable $th) {
            $deferred->reject($th);
            return $deferred->promise();
        }

        // The same as new VoiceClient(...)
        $this->clients[$channel->guild_id] = VoiceClient::make(
            $this->bot,
            $channel,
            ['dnsConfig' => $discord->options['dnsConfig']],
            $deaf,
            $mute,
            $deferred,
            $this,
            false
        );

        $discord->on(Event::VOICE_STATE_UPDATE, fn ($state) => $this->stateUpdate($state, $channel));
        // Creates Voice Client and waits for the voice server update.
        $discord->on(Event::VOICE_SERVER_UPDATE, fn ($state, Discord $discord) => $this->serverUpdate($state, $channel, $discord, $deferred));

        $discord->send(VoicePayload::new(
            Op::OP_VOICE_STATE_UPDATE,
            [
                'guild_id' => $channel->guild_id,
                'channel_id' => $channel->id,
                'self_mute' => $mute,
                'self_deaf' => $deaf,
            ],
        ));

        return $deferred->promise();
    }

    /**
     * Retrieves the voice client for a specific guild.
     *
     * @param string|int $guildId
     *
     * @return \Discord\Voice\VoiceClient|null
     */
    public function getClient(string|int|Channel $guildChannelOrId): ?VoiceClient
    {
        if ($guildChannelOrId instanceof Channel) {
            $guildChannelOrId = $guildChannelOrId->guild_id;
        }

        if (! isset($this->clients[$guildChannelOrId])) {
            return null;
        }

        return $this->clients[$guildChannelOrId];
    }

    /**
     * Handles the voice state update event to update session information for the voice client.
     *
     * @param \Discord\Parts\WebSockets\VoiceStateUpdate $state
     * @param \Discord\Parts\Channel\Channel $channel
     *
     * @return void
     */
    protected function stateUpdate(VoiceStateUpdate $state, Channel $channel): void
    {
        if ($state->guild_id != $channel->guild_id) {
            return; // This voice state update isn't for our guild.
        }

        $this->getClient($channel)
            ->setData(['session' => $state->session_id, 'deaf' => $state->deaf, 'mute' => $state->mute]);

        $this->bot->getLogger()->info('received session id for voice session', ['guild' => $channel->guild_id, 'session_id' => $state->session_id]);
    }

    /**
     * Handles the voice server update event to create a new voice client with the provided state.
     *
     * @param \Discord\Parts\WebSockets\VoiceServerUpdate $state
     * @param \Discord\Parts\Channel\Channel $channel
     * @param \Discord\Discord $discord
     * @param \React\Promise\Deferred $deferred
     *
     * @return void
     */
    protected function serverUpdate(VoiceServerUpdate $state, Channel $channel, Discord $discord, Deferred $deferred): void
    {
        if ($state->guild_id !== $channel->guild_id) {
            return; // This voice server update isn't for our guild.
        }

        $this->bot->getLogger()->info('received token and endpoint for voice session', [
            'guild' => $channel->guild_id,
            'token' => $state->token,
            'endpoint' => $state->endpoint
        ]);

        $client = $this->getClient($channel);

        $client->setData(array_merge(
            $client->data,
            [
                'token' => $state->token,
                'endpoint' => $state->endpoint,
                'session' => $client->data['session'] ?? null,
            ],
            ['dnsConfig' => $discord->options['dnsConfig']])
        );
    }

}
