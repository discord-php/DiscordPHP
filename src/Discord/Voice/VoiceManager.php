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
use Psr\Log\LoggerInterface;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

final class VoiceManager
{
    use EventEmitterTrait;

    /**
     * @param \Ratchet\Client\WebSocket $botWs
     * @param \React\EventLoop\LoopInterface $loop
     * @param \Psr\Log\LoggerInterface $logger
     * @param int $botId
     * @param array<VoiceClient> $clients
     */
    public function __construct(
        protected WebSocket $botWs,
        protected LoopInterface $loop,
        protected LoggerInterface $logger,
        protected int $botId,
        public array $clients = [],
    ) {
    }

    public function createClientAndJoinChannel(
        Channel $channel,
        Discord $discord,
        bool $mute = false,
        bool $deaf = true,
    )
    {
        $deferred = new Deferred();

        if (! $channel->isVoiceBased()) {
            $deferred->reject(new \RuntimeException('Channel must allow voice.'));

            return $deferred->promise();
        }

        if (isset($this->clients[$channel->guild_id])) {
            $deferred->reject(new \RuntimeException('You cannot join more than one voice channel per guild.'));

            return $deferred->promise();
        }

        $this->clients[$channel->guild_id] = ['data' => []];
        $this->clients[$channel->guild_id]['data'] = [
            'user_id' => $this->botId,
            'deaf' => $deaf,
            'mute' => $mute,
        ];

        $discord->once(Event::VOICE_STATE_UPDATE, fn ($state) => $this->stateUpdate($state, $channel));
        $discord->once(Event::VOICE_SERVER_UPDATE, fn ($state, $discord) => $this->serverUpdate($state, $channel, $discord, $deferred));

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

    public function getClient(string $guildId): ?VoiceClient
    {
        if (! isset($this->clients[$guildId])) {
            return null;
        }

        return $this->clients[$guildId];
    }

    protected function stateUpdate($state, $channel): void
    {
        if ($state->guild_id != $channel->guild_id) {
            return; // This voice state update isn't for our guild.
        }

        $this->clients[$channel->guild_id]['data']['session'] = $state->session_id;
        $this->logger->info('received session id for voice session', ['guild' => $channel->guild_id, 'session_id' => $state->session_id]);
    }

    protected function serverUpdate($state, Channel $channel, $discord, Deferred $deferred): void
    {
        if ($state->guild_id !== $channel->guild_id) {
            return; // This voice server update isn't for our guild.
        }

        $data = $this->clients[$channel->guild_id]['data'];
        unset($this->clients[$channel->guild_id]['data']);

        $data['token'] = $state->token;
        $data['endpoint'] = $state->endpoint;
        $data['dnsConfig'] = $discord->options['dnsConfig'];

        $this->logger->info('received token and endpoint for voice session', [
            'guild' => $channel->guild_id,
            'token' => $state->token,
            'endpoint' => $state->endpoint
        ]);

        $client = new VoiceClient($discord, $this->botWs, $channel, $data);

        $client->once('ready', function () use ($client, $deferred, $channel) {
                $this->logger->info('voice client is ready');
                $this->clients[$channel->guild_id] = $client;

                $client->setBitrate($channel->bitrate);

                $this->logger->info('set voice client bitrate', ['bitrate' => $channel->bitrate]);
                $deferred->resolve($client);
            })
            ->once('error', function ($e) use ($deferred) {
                $this->logger->error('error initializing voice client', ['e' => $e->getMessage()]);
                $deferred->reject($e);
            })
            ->once('close', function () use ($channel) {
                $this->logger->warning('voice client closed');
                unset($this->clients[$channel->guild_id]);
            })
            ->start();
    }

    protected function sendStateUpdate(Channel $channel, bool $mute = false, bool $deaf = true): void
    {
        $this->botWs->send(json_encode([
            'op' => Op::OP_VOICE_STATE_UPDATE,
            'd' => [
                'guild_id' => $channel->guild_id,
                'channel_id' => $channel->id,
                'self_mute' => $mute,
                'self_deaf' => $deaf,
            ],
        ]));
    }
}
