<?php

declare(strict_types=1);

namespace Discord\Voice;

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Voice\VoiceClient;
use Discord\WebSockets\Event;
use Discord\WebSockets\Op;
use Discord\WebSockets\VoicePayload;
use Evenement\EventEmitterTrait;
use React\Promise\Deferred;

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
     * @return \React\Promise\PromiseInterface<T>
     */
    public function createClientAndJoinChannel(
        Channel $channel,
        Discord $discord,
        bool $mute = false,
        bool $deaf = true,
    )
    {
        $deferred = new Deferred();

        try {
            if (! $channel->isVoiceBased()) {
                throw new \RuntimeException('Channel must allow voice.');
            }

            if (! $channel->canJoin()) {
                throw new \RuntimeException('The bot must have proper permissions to join this channel.');
            }

            if (! $channel->canSpeak() && ! $mute) {
                throw new \RuntimeException('The bot must have permission to speak in this channel.');
            }

            if (isset($this->clients[$channel->guild_id])) {
                throw new \RuntimeException('You cannot join more than one voice channel per guild.');
            }
        } catch (\Throwable $th) {
            $deferred->reject($th);
            return $deferred->promise();
        }

        $this->clients[$channel->guild_id] = ['data' => []];
        $this->clients[$channel->guild_id]['data'] = [
            'user_id' => $this->bot->id,
            'deaf' => $deaf,
            'mute' => $mute,
        ];

        $discord->once(Event::VOICE_STATE_UPDATE, fn ($state) => $this->stateUpdate($state, $channel));
        // Creates Voice Client and waits for the voice server update.
        $discord->once(Event::VOICE_SERVER_UPDATE, fn ($state, Discord $discord) => $this->serverUpdate($state, $channel, $discord, $deferred));

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

    public function getClient(string|int $guildId): ?VoiceClient
    {
        if (! isset($this->clients[$guildId])) {
            return null;
        }

        return $this->clients[$guildId];
    }

    protected function stateUpdate($state, Channel $channel): void
    {
        if ($state->guild_id != $channel->guild_id) {
            return; // This voice state update isn't for our guild.
        }

        $this->clients[$channel->guild_id]['data']['session'] = $state->session_id;
        $this->bot->getLogger()->info('received session id for voice session', ['guild' => $channel->guild_id, 'session_id' => $state->session_id]);
    }

    protected function serverUpdate($state, Channel $channel, Discord $discord, Deferred $deferred): void
    {
        if ($state->guild_id !== $channel->guild_id) {
            return; // This voice server update isn't for our guild.
        }

        $data = $this->clients[$channel->guild_id]['data'];
        unset($this->clients[$channel->guild_id]['data']);

        $data['token'] = $state->token;
        $data['endpoint'] = $state->endpoint;
        $data['dnsConfig'] = $discord->options['dnsConfig'];

        $this->bot->getLogger()->info('received token and endpoint for voice session', [
            'guild' => $channel->guild_id,
            'token' => $state->token,
            'endpoint' => $state->endpoint
        ]);

        VoiceClient::make($discord, $channel, $data, deferred: $deferred, manager: $this);
    }

}
