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

namespace Discord\Voice;

use Discord\Discord;
use Discord\Exceptions\FileNotFoundException;
use Discord\Exceptions\OutdatedDCAException;
use Discord\Exceptions\Voice\AudioAlreadyPlayingException;
use Discord\Exceptions\Voice\ClientNotReadyException;
use Discord\Helpers\Buffer as RealBuffer;
use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Channel;
use Discord\Parts\EventData\VoiceSpeaking;
use Discord\Voice\Client\Packet;
use Discord\Voice\Client\UDP;
use Discord\Voice\Client\User;
use Discord\Voice\Client\WS;
use Discord\Voice\Processes\Dca;
use Discord\Voice\Processes\Ffmpeg;
use Discord\Voice\Processes\OpusFfi;
use Discord\WebSockets\Op;
use Discord\WebSockets\Payload;
use Discord\WebSockets\VoicePayload;
use Evenement\EventEmitter;
use Ratchet\Client\WebSocket;
use React\ChildProcess\Process;
use React\Datagram\Socket;
use React\Dns\Config\Config;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Stream\ReadableResourceStream as Stream;
use React\Stream\ReadableStreamInterface;

use function Discord\logger;
use function Discord\loop;

/**
 * The Discord voice client.
 *
 * @since 10.19.0
 */
class VoiceClient extends EventEmitter
{
    /**
     * Is the voice client ready?
     *
     * @var bool Whether the voice client is ready.
     */
    public bool $ready = false;

    /**
     * The voice WebSocket instance.
     *
     * @var WebSocket|null The voice WebSocket client.
     */
    public ?WebSocket $ws;

    /**
     * The UDP client instance.
     *
     * @var null|Socket|\Discord\Voice\Client\UDP
     */
    public ?UDP $udp;

    /**
     * The Voice WebSocket endpoint.
     *
     * @var string|null The endpoint the Voice WebSocket and UDP client will connect to.
     */
    public ?string $endpoint;

    /**
     * The UDP heartbeat interval.
     *
     * @var int|null How often we send a heartbeat packet.
     */
    public ?int $heartbeatInterval = null;

    /**
     * The Voice WebSocket heartbeat timer.
     *
     * @var TimerInterface|null The heartbeat periodic timer.
     */
    public ?TimerInterface $heartbeat = null;

    /**
     * The SSRC value.
     *
     * @var int|null The SSRC value used for RTP.
     */
    public ?int $ssrc;

    /**
     * The sequence of audio packets being sent.
     *
     * @var int The sequence of audio packets.
     */
    public ?int $seq = 0;

    /**
     * The timestamp of the last packet.
     *
     * @var int The timestamp the last packet was constructed.
     */
    public ?int $timestamp = 0;

    /**
     * Are we currently speaking?
     *
     * @var bool
     */
    public bool $speaking = false;

    /**
     * Whether the voice client is currently paused.
     *
     * @var bool
     */
    public bool $paused = false;

    /**
     * Have we sent the login frame yet?
     *
     * @var bool Whether we have sent the login frame.
     */
    public bool $sentLoginFrame = false;

    /**
     * The time we started sending packets.
     *
     * @var float|int|null The time we started sending packets.
     */
    public null|float|int $startTime;

    /**
     * The size of audio frames, in milliseconds.
     *
     * @var int The size of audio frames.
     */
    public int $frameSize = 20;

    /**
     * Collection of the status of people speaking.
     *
     * @var ExCollectionInterface<VoiceSpeaking> Status of people speaking.
     */
    public $speakingStatus;

    /**
     * Collection of voice decoders.
     *
     * @var ExCollectionInterface Voice decoders.
     */
    public $voiceDecoders;

    /**
     * Voice audio recieve streams.
     *
     * @deprecated 10.5.0 Use receiveStreams instead.
     *
     * @var array<ReceiveStream>|null Voice audio recieve streams.
     */
    public ?array $recieveStreams;

    /**
     * Voice audio receive streams.
     *
     * @var array<ReceiveStream>|null Voice audio recieve streams.
     */
    public ?array $receiveStreams;

    /**
     * The volume the audio will be encoded with.
     *
     * @var int The volume that the audio will be encoded in.
     */
    protected int $volume = 100;

    /**
     * The audio application to encode with.
     *
     * Available: voip, audio (default), lowdelay
     *
     * @var string The audio application.
     */
    protected string $audioApplication = 'audio';

    /**
     * The bitrate to encode with.
     *
     * @var int Encoding bitrate.
     */
    protected int $bitrate = 128000;

    /**
     * Is the voice client reconnecting?
     *
     * @var bool Whether the voice client is reconnecting.
     */
    public bool $reconnecting = false;

    /**
     * Is the voice client being closed by user?
     *
     * @var bool Whether the voice client is being closed by user.
     */
    public bool $userClose = false;

    /**
     * The Config for DNS Resolver.
     *
     * @var Config|string|null
     */
    public null|string|Config $dnsConfig;

    /**
     * readopus Timer.
     *
     * @var TimerInterface Timer
     */
    public TimerInterface $readOpusTimer;

    /**
     * Audio Buffer.
     *
     * @var RealBuffer|null The Audio Buffer
     */
    public null|RealBuffer $buffer;

    /**
     * Current clients connected to the voice chat
     *
     * @var array
     */
    public array $clientsConnected = [];

    /**
     * @var TimerInterface
     */
    public $monitorProcessTimer;

    /**
     * Users in the current voice channel.
     *
     * @var array<User> Users in the current voice channel.
     */
    public array $users;

    /**
     * Time in which the streaming started.
     *
     * @var int
     */
    public int $streamTime = 0;

    /**
     * Whether the current voice client is enabled to record audio.
     *
     * @var bool
     */
    protected bool $shouldRecord = false;

    /**
     * Constructs the Voice client instance
     *
     * @param \Discord\Discord $bot The Discord instance.
     * @param \Discord\Parts\Channel\Channel $channel
     * @param array $data
     * @param bool $deaf Default: false
     * @param bool $mute Default: false
     * @param Deferred|null $deferred
     * @param VoiceManager|null $manager
     */
    public function __construct(
        public Discord $bot,
        public Channel $channel,
        public array $data = [],
        public bool $deaf = false,
        public bool $mute = false,
        protected ?Deferred $deferred = null,
        protected ?VoiceManager &$manager = null,
        protected bool $shouldBoot = true
    ) {
        $this->deaf = $this->data['deaf'] ?? false;
        $this->mute = $this->data['mute'] ?? false;

        $this->data = [
            'user_id' => $this->bot->id,
            'deaf' => $this->deaf,
            'mute' => $this->mute,
            'session' => $this->data['session'] ?? null,
        ];

        if ($this->shouldBoot) {
            $this->boot();
        }
    }

    /**
     * Starts the voice client.
     *
     * @return bool
     */
    public function start(): bool
    {
        if (! Ffmpeg::checkForFFmpeg()) {
            return false;
        }

        WS::make($this);
        return true;
    }

    /**
     * Plays a file/url on the voice stream.
     *
     * @param string $file     The file/url to play.
     * @param int    $channels Deprecated, Discord only supports 2 channels.
     *
     * @throws FileNotFoundException
     * @throws \RuntimeException
     *
     * @return PromiseInterface
     */
    public function playFile(string $file, int $channels = 2): PromiseInterface
    {
        $deferred = new Deferred();
        $notAValidFile = filter_var($file, FILTER_VALIDATE_URL) === false && ! file_exists($file);

        if (
            $notAValidFile || (! $this->ready) || $this->speaking
        ) {
            if ($notAValidFile) {
                $deferred->reject(new FileNotFoundException("Could not find the file \"{$file}\"."));
            }

            if (! $this->ready) {
                $deferred->reject(new ClientNotReadyException());
            }

            if ($this->speaking) {
                $deferred->reject(new AudioAlreadyPlayingException());
            }

            return $deferred->promise();
        }

        $process = Ffmpeg::encode($file, volume: $this->getDbVolume());
        $process->start($this->bot->loop);

        return $this->playOggStream($process);
    }

    /**
     * Plays a raw PCM16 stream.
     *
     * @param resource|Stream $stream    The stream to be encoded and sent.
     * @param int             $channels  How many audio channels the PCM16 was encoded with.
     * @param int             $audioRate Audio sampling rate the PCM16 was encoded with.
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException Thrown when the stream passed to playRawStream is not a valid resource.
     *
     * @return PromiseInterface
     */
    public function playRawStream($stream, int $channels = 2, int $audioRate = 48000): PromiseInterface
    {
        $deferred = new Deferred();

        if (! $this->ready) {
            $deferred->reject(new \RuntimeException('Voice Client is not ready.'));

            return $deferred->promise();
        }

        if ($this->speaking) {
            $deferred->reject(new \RuntimeException('Audio already playing.'));

            return $deferred->promise();
        }

        if (! is_resource($stream) && ! $stream instanceof Stream) {
            $deferred->reject(new \InvalidArgumentException('The stream passed to playRawStream was not an instance of resource or ReactPHP Stream.'));

            return $deferred->promise();
        }

        if (is_resource($stream)) {
            $stream = new Stream($stream, $this->bot->loop);
        }

        $process = Ffmpeg::encode(volume: $this->getDbVolume(), preArgs: [
            '-f', 's16le',
            '-ac', $channels,
            '-ar', $audioRate,
        ]);
        $process->start($this->bot->loop);
        $stream->pipe($process->stdin);

        return $this->playOggStream($process);
    }

    /**
     * Plays an Ogg Opus stream.
     *
     * @param resource|Process|Stream $stream The Ogg Opus stream to be sent.
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     *
     * @return PromiseInterface
     */
    public function playOggStream($stream): PromiseInterface
    {
        $deferred = new Deferred();

        if (! $this->isReady()) {
            $deferred->reject(new \RuntimeException('Voice client is not ready yet.'));

            return $deferred->promise();
        }

        if ($this->speaking) {
            $deferred->reject(new \RuntimeException('Audio already playing.'));

            return $deferred->promise();
        }

        if ($stream instanceof Process) {
            $stream->stderr->on('data', function ($d) {
                if (empty($d)) {
                    return;
                }

                $this->emit('stderr', [$d, $this]);
            });

            $stream = $stream->stdout;
        }

        if (is_resource($stream)) {
            $stream = new Stream($stream, $this->bot->loop);
        }

        if (! ($stream instanceof ReadableStreamInterface)) {
            $deferred->reject(new \InvalidArgumentException('The stream passed to playOggStream was not an instance of resource, ReactPHP Process, ReactPHP Readable Stream'));

            return $deferred->promise();
        }

        $this->buffer = new RealBuffer($this->bot->loop);
        $stream->on('data', function ($d) {
            $this->buffer->write($d);
        });

        /** @var OggStream */
        $ogg = null;

        $loops = 0;

        $this->setSpeaking(true);

        OggStream::fromBuffer($this->buffer)->then(function (OggStream $os) use ($deferred, &$ogg, &$loops) {
            $ogg = $os;
            $this->startTime = microtime(true) + 0.5;
            $this->readOpusTimer = $this->bot->loop->addTimer(0.5, fn () => $this->readOggOpus($deferred, $ogg, $loops));
        });

        return $deferred->promise();
    }

    /**
     * Reads Ogg Opus packets and sends them to the voice server.
     *
     * @param Deferred $deferred The deferred promise.
     * @param OggStream $ogg The Ogg stream to read packets from.
     * @param int &$loops The number of loops that have been executed.
     */
    protected function readOggOpus(Deferred $deferred, OggStream &$ogg, int &$loops): void
    {
        $this->readOpusTimer = null;

        $loops += 1;

        // If the client is paused, delay by frame size and check again.
        if ($this->paused) {
            $this->udp->insertSilence();
            $this->readOpusTimer = $this->bot->loop->addTimer($this->frameSize / 1000, fn () => $this->readOggOpus($deferred, $ogg, $loops));

            return;
        }

        $ogg->getPacket()->then(function ($packet) use (&$loops, $deferred) {
            // EOF for Ogg stream.
            if (null === $packet) {
                $this->reset();
                $deferred->resolve(null);

                return;
            }

            // increment sequence
            // uint16 overflow protection
            if (++$this->seq >= 2 ** 16) {
                $this->seq = 0;
            }

            $this->udp->sendBuffer($packet);

            // increment timestamp
            // uint32 overflow protection
            if (($this->timestamp += ($this->frameSize * 48)) >= 2 ** 32) {
                $this->timestamp = 0;
            }

            $nextTime = $this->startTime + (20.0 / 1000.0) * $loops;
            $delay = $nextTime - microtime(true);

            $this->readOpusTimer = $this->bot->loop->addTimer($delay, fn () => $this->readOggOpus($deferred, $ogg, $loops));
        }, function () use ($deferred) {
            $this->reset();
            $deferred->resolve(null);
        });
    }

    /**
     * Plays a DCA stream.
     *
     * @param resource|Process|Stream $stream The DCA stream to be sent.
     *
     * @return PromiseInterface
     * @throws \Exception
     *
     * @deprecated 10.0.0 DCA is now deprecated in DiscordPHP, switch to using
     *                    `playOggStream` with raw Ogg Opus.
     */
    public function playDCAStream($stream): PromiseInterface
    {
        $deferred = new Deferred();

        if (! $this->isReady()) {
            $deferred->reject(new \Exception('Voice client is not ready yet.'));

            return $deferred->promise();
        }

        if ($this->speaking) {
            $deferred->reject(new \Exception('Audio already playing.'));

            return $deferred->promise();
        }

        if ($stream instanceof Process) {
            $stream->stderr->on('data', function ($d) {
                if (empty($d)) {
                    return;
                }

                $this->emit('stderr', [$d, $this]);
            });

            $stream = $stream->stdout;
        }

        if (is_resource($stream)) {
            $stream = new Stream($stream, $this->bot->loop);
        }

        if (! ($stream instanceof ReadableStreamInterface)) {
            $deferred->reject(new \Exception('The stream passed to playDCAStream was not an instance of resource, ReactPHP Process, ReactPHP Readable Stream'));

            return $deferred->promise();
        }

        $this->buffer = new RealBuffer($this->bot->loop);
        $stream->on('data', function ($d) {
            $this->buffer->write($d);
        });

        $this->setSpeaking(true);

        // Read magic byte header
        $this->buffer->read(4)->then(function ($mb) {
            if ($mb !== Dca::DCA_VERSION) {
                throw new OutdatedDCAException('The DCA magic byte header was not correct.');
            }

            // Read JSON length
            return $this->buffer->readInt32();
        })->then(function ($jsonLength) {
            // Read JSON content
            return $this->buffer->read($jsonLength);
        })->then(function ($metadata) use ($deferred) {
            $metadata = json_decode($metadata, true);

            if (null !== $metadata) {
                $this->frameSize = $metadata['opus']['frame_size'] / 48;
            }

            $this->startTime = microtime(true) + 0.5;
            $this->readOpusTimer = $this->bot->loop->addTimer(0.5, fn () => $this->readDCAOpus($deferred));
        });

        return $deferred->promise();
    }

    /**
     * Reads and processes a single Opus audio frame from a DCA (Discord Compressed Audio) stream.
     *
     * @param Deferred $deferred A promise that will be resolved when the reading process completes or fails.
     *
     * @return void
     */
    protected function readDCAOpus(Deferred $deferred): void
    {
        $this->readOpusTimer = null;

        // If the client is paused, delay by frame size and check again.
        if ($this->paused) {
            $this->udp->insertSilence();
            $this->readOpusTimer = $this->bot->loop->addTimer($this->frameSize / 1000, fn () => $this->readDCAOpus($deferred));

            return;
        }

        // Read opus length
        $this->buffer->readInt16(1000)->then(function ($opusLength) {
            // Read opus data
            return $this->buffer->read($opusLength, null, 1000);
        })->then(function ($opus) use ($deferred) {
            $this->udp->sendBuffer($opus);

            // increment sequence
            // uint16 overflow protection
            if (++$this->seq >= 2 ** 16) {
                $this->seq = 0;
            }

            // increment timestamp
            // uint32 overflow protection
            if (($this->timestamp += ($this->frameSize * 48)) >= 2 ** 32) {
                $this->timestamp = 0;
            }

            $this->readOpusTimer = $this->bot->loop->addTimer(($this->frameSize - 1) / 1000, fn () => $this->readDCAOpus($deferred));
        }, function () use ($deferred) {
            $this->reset();
            $deferred->resolve(null);
        });
    }

    /**
     * Resets the voice client.
     */
    protected function reset(): void
    {
        if ($this->readOpusTimer) {
            $this->bot->loop->cancelTimer($this->readOpusTimer);
            $this->readOpusTimer = null;
        }

        $this->setSpeaking(false);
        $this->streamTime = 0;
        $this->startTime = 0;
        $this->paused = false;
        $this->silenceRemaining = 5;
    }

    /**
     * Sets the speaking value of the client.
     *
     * @param bool $speaking Whether the client is speaking or not.
     *
     * @throws \RuntimeException
     */
    public function setSpeaking(bool $speaking = true): void
    {
        if ($this->speaking == $speaking) {
            return;
        }

        if (! $this->ready) {
            throw new \RuntimeException('Voice Client is not ready.');
        }

        $this->ws->send(VoicePayload::new(
            Op::VOICE_SPEAKING,
            [
                'speaking' => $speaking,
                'delay' => 0,
                'ssrc' => $this->ssrc,
            ],
        ));

        $this->speaking = $speaking;
    }

    /**
     * Switches voice channels.
     *
     * @param null|Channel $channel The channel to switch to.
     *
     * @throws \InvalidArgumentException
     */
    public function switchChannel(?Channel $channel): self
    {
        if (isset($channel) && ! $channel->isVoiceBased()) {
            throw new \InvalidArgumentException("Channel must be a voice channel to be able to switch, given type {$channel->type}.");
        }

        // We allow the user to switch to null, which will disconnect them from the voice channel.
        if (! isset($channel)) {
            $channel = $this->channel;
            $this->userClose = true;
        } else {
            $this->channel = $channel;
        }

        $this->mainSend(VoicePayload::new(
            Op::OP_VOICE_STATE_UPDATE,
            [
                'guild_id' => $channel->guild_id,
                'channel_id' => $channel?->id ?? null,
                'self_mute' => $this->mute,
                'self_deaf' => $this->deaf,
            ],
        ));

        return $this;
    }

    /**
     * Disconnects the bot from the current voice channel.
     *
     * @return \Discord\Voice\VoiceClient
     */
    public function disconnect(): static
    {
        $this->switchChannel(null);

        return $this;
    }

    /**
     * Sets the bitrate.
     *
     * @param int $bitrate The bitrate to set.
     *
     * @throws \DomainException
     * @throws \RuntimeException
     */
    public function setBitrate(int $bitrate): void
    {
        if ($bitrate < 8000 || $bitrate > 384000) {
            throw new \DomainException("{$bitrate} is not a valid option. The bitrate must be between 8,000 bps and 384,000 bps.");
        }

        if ($this->speaking) {
            throw new \RuntimeException('Cannot change bitrate while playing.');
        }

        $this->bitrate = $bitrate;
    }

    /**
     * Sets the volume.
     *
     * @param int $volume The volume to set.
     *
     * @throws \DomainException
     * @throws \RuntimeException
     */
    public function setVolume(int $volume): void
    {
        if ($volume < 0 || $volume > 100) {
            throw new \DomainException("{$volume}% is not a valid option. The bitrate must be between 0% and 100%.");
        }

        if ($this->speaking) {
            throw new \RuntimeException('Cannot change volume while playing.');
        }

        $this->volume = $volume;
    }

    /**
     * Sets the audio application.
     *
     * @param string $app The audio application to set.
     *
     * @throws \DomainException
     * @throws \RuntimeException
     */
    public function setAudioApplication(string $app): void
    {
        $legal = ['voip', 'audio', 'lowdelay'];

        if (! in_array($app, $legal)) {
            throw new \DomainException("{$app} is not a valid option. Valid options are: ".implode(', ', $legal));
        }

        if ($this->speaking) {
            throw new \RuntimeException('Cannot change audio application while playing.');
        }

        $this->audioApplication = $app;
    }

    /**
     * Sends a message to the main websocket.
     *
     * @param Payload $data The data to send to the main WebSocket.
     */
    protected function mainSend($data): void
    {
        $this->bot->send($data);
    }

    /**
     * Changes your mute and deaf value.
     *
     * @param bool $mute Whether you should be muted.
     * @param bool $deaf Whether you should be deaf.
     *
     * @throws \RuntimeException
     */
    public function setMuteDeaf(bool $mute, bool $deaf): void
    {
        if (! $this->ready) {
            throw new \RuntimeException('The voice client must be ready before you can set mute or deaf.');
        }

        $this->mute = $mute;
        $this->deaf = $deaf;

        $this->mainSend(VoicePayload::new(
            Op::OP_VOICE_STATE_UPDATE,
            [
                'guild_id' => $this->channel->guild_id,
                'channel_id' => $this->channel->id,
                'self_mute' => $mute,
                'self_deaf' => $deaf,
            ],
        ));

        $this->udp->removeListener('message', [$this, 'handleAudioData']);

        if (! $deaf) {
            $this->udp->on('message', [$this, 'handleAudioData']);
        }
    }

    /**
     * Pauses the current sound.
     *
     * @throws \RuntimeException
     */
    public function pause(): void
    {
        if (! $this->speaking) {
            throw new \RuntimeException('Audio must be playing to pause it.');
        }

        if ($this->paused) {
            throw new \RuntimeException('Audio is already paused.');
        }

        $this->paused = true;
        $this->udp->refreshSilenceFrames();
    }

    /**
     * Unpauses the current sound.
     *
     * @throws \RuntimeException
     */
    public function unpause(): void
    {
        if (! $this->speaking) {
            throw new \RuntimeException('Audio must be playing to unpause it.');
        }

        if (! $this->paused) {
            throw new \RuntimeException('Audio is already playing.');
        }

        $this->paused = false;
        $this->timestamp = microtime(true) * 1000;
    }

    /**
     * Stops the current sound.
     *
     * @throws \RuntimeException
     */
    public function stop(): void
    {
        if (! $this->speaking) {
            throw new \RuntimeException('Audio must be playing to stop it.');
        }

        $this->buffer->end();
        $this->udp->insertSilence();
        $this->reset();
    }

    /**
     * Closes the voice client.
     *
     * @throws \RuntimeException
     */
    public function close(): void
    {
        if (! $this->ready) {
            throw new \RuntimeException('Voice Client is not connected.');
        }

        if ($this->speaking) {
            $this->stop();
            $this->setSpeaking(false);
        }

        $this->ready = false;

        // Close processes for audio encoding
        if (count($this?->voiceDecoders ?? []) > 0) {
            foreach ($this->voiceDecoders as $decoder) {
                $decoder->close();
            }
        }

        if (count($this?->receiveStreams ?? []) > 0) {
            foreach ($this->receiveStreams as $stream) {
                $stream->close();
            }
        }

        if (count($this?->speakingStatus ?? []) > 0) {
            foreach ($this->speakingStatus as $ss) {
                $this->removeDecoder($ss);
            }
        }

        $this->mainSend(VoicePayload::new(
            Op::OP_VOICE_STATE_UPDATE,
            [
                'guild_id' => $this->channel->guild_id,
                'channel_id' => null,
                'self_mute' => true,
                'self_deaf' => true,
            ],
        ));

        $this->userClose = true;
        $this->ws->close();
        $this->udp->close();

        $this->heartbeatInterval = null;

        if (null !== $this->heartbeat) {
            $this->bot->loop->cancelTimer($this->heartbeat);
            $this->heartbeat = null;
        }

        $this->seq = 0;
        $this->timestamp = 0;
        $this->sentLoginFrame = false;
        $this->startTime = null;
        $this->streamTime = 0;
        $this->speakingStatus = new Collection([], 'ssrc');

        $this->emit('close');
    }

    /**
     * Checks if the user is speaking.
     *
     * @param string|int|null $id Either the User ID or SSRC (if null, return bots speaking status).
     *
     * @return bool Whether the user is speaking.
     */
    public function isSpeaking($id = null): bool
    {
        return match(true) {
            ! isset($id) => $this->speaking,
            $user = $this->speakingStatus->get('user_id', $id) => $user->speaking,
            $ssrc = $this->speakingStatus->get('ssrc', $id) => $ssrc->speaking,
            default => false,
        };
    }

    /**
     * Handles a voice state update.
     * NOTE: This object contains the data as the VoiceStateUpdate Part.
     * @see \Discord\Parts\WebSockets\VoiceStateUpdate
     *
     * @param object $data The WebSocket data.
     */
    public function handleVoiceStateUpdate(object $data): void
    {
        $ss = $this->speakingStatus->get('user_id', $data->user_id);

        if (null === $ss) {
            return; // not in our channel
        }

        if ($data->channel_id == $this->channel->id) {
            return; // ignore, just a mute/deaf change
        }

        $this->removeDecoder($ss);
    }

    /**
     * Removes and closes the voice decoder associated with the given SSRC.
     *
     * @param object $ss An object containing the SSRC (Synchronization Source identifier).
     *                   Expected to have a property 'ssrc'.
     *
     * @return void
     */
    protected function removeDecoder($ss): void
    {
        $decoder = $this->voiceDecoders[$ss->ssrc] ?? null;

        if (null === $decoder) {
            return; // no voice decoder to remove
        }

        $decoder->close();
        unset(
            $this->voiceDecoders[$ss->ssrc],
            $this->speakingStatus[$ss->ssrc],
            $this->receiveStreams[$ss->ssrc]
        );
    }

    /**
     * Gets a recieve voice stream.
     *
     * @param int|string $id Either a SSRC or User ID.
     *
     * @deprecated 10.5.0 Use getReceiveStream instead.
     *
     * @return RecieveStream|ReceiveStream|null
     */
    public function getRecieveStream($id)
    {
        return $this->getReceiveStream($id);
    }

    /**
     * Gets a receive voice stream.
     *
     * @param int|string $id Either a SSRC or User ID.
     *
     * @return ReceiveStream|null
     */
    public function getReceiveStream($id)
    {
        if (isset($this->receiveStreams[$id])) {
            return $this->receiveStreams[$id];
        }

        foreach ($this->speakingStatus as $status) {
            if ($status?->user_id == $id) {
                return $this->receiveStreams[$status?->ssrc];
            }
        }

        return null;
    }

    /**
     * Handles raw opus data from the UDP server.
     *
     * @param Packet $voicePacket The data from the UDP server.
     */
    public function handleAudioData(Packet $voicePacket): void
    {
        if (! $this->shouldRecord) {
            // If we are not recording, we don't need to handle audio data.
            return;
        }

        $message = $voicePacket?->decryptedAudio ?? null;

        if (! $message || ! $this->speakingStatus->get('ssrc', $voicePacket->getSSRC())) {
            // We don't have a speaking status for this SSRC
            // Probably a "ping" to the udp socket
            // There's no message or the message threw an error inside the decrypt function
            $this->bot->logger->warning('No audio data.', ['voicePacket' => $voicePacket]);
            return;
        }

        $this->emit('raw', [$message, $this]);

        $ss = $this->speakingStatus->get('ssrc', $voicePacket->getSSRC());
        $decoder = $this->voiceDecoders[$voicePacket->getSSRC()] ?? null;

        if (null === $ss) {
            // for some reason we don't have a speaking status
            $this->bot->logger->warning('Unknown SSRC.', ['ssrc' => $voicePacket->getSSRC(), 't' => $voicePacket->getTimestamp()]);
            return;
        }

        if (null === $decoder) {
            // make a decoder
            if (! isset($this->receiveStreams[$ss->ssrc])) {
                $this->receiveStreams[$ss->ssrc] = new ReceiveStream();

                $this->receiveStreams[$ss->ssrc]->on('pcm', function ($d) {
                    $this->emit('channel-pcm', [$d, $this]);
                });

                $this->receiveStreams[$ss->ssrc]->on('opus', function ($d) {
                    $this->emit('channel-opus', [$d, $this]);
                });
            }

            $this->createDecoder($ss);
            $decoder = $this->voiceDecoders[$ss->ssrc] ?? null;
        }

        if ($decoder->stdin->isWritable() === false) {
            logger()->warning('Decoder stdin is not writable.', ['ssrc' => $ss->ssrc]);
            return; // decoder stdin is not writable, cannot write audio data.
            // This should be either restarted or checked if the decoder is still running.
        }

        if (
            empty($voicePacket->decryptedAudio)
            || $voicePacket->decryptedAudio === "\xf8\xff\xfe" // Opus silence frame
            || strlen($voicePacket->decryptedAudio) < 8 // Opus frame is at least 8 bytes
        ) {
            return; // no audio data to write
        }

        $data = OpusFfi::decode($voicePacket->decryptedAudio);

        if (empty(trim($data))) {
            logger()->debug('Received empty audio data.', ['ssrc' => $ss->ssrc]);
            return; // no audio data to write
        }

        $decoder->stdin->write($data);
    }

    /**
     * Creates and initializes a decoder process for the given stream session.
     *
     * @param object $ss The stream session object containing information such as SSRC and user ID.
     */
    protected function createDecoder($ss): void
    {
        $decoder = Ffmpeg::decode((string) $ss->ssrc);
        $decoder->start(loop());

        $decoder->stdout->on('data', function ($data) use ($ss) {
            if (empty($data)) {
                return; // no data to process, should be ignored
            }

            // Emit the decoded opus data
            $this->receiveStreams[$ss->ssrc]->writeOpus($data);
        });

        $decoder->stderr->on('data', function ($data) use ($ss) {
            if (empty($data)) {
                return; // no data to process
            }

            $this->emit("voice.{$ss->ssrc}.stderr", [$data, $this]);
            $this->emit("voice.{$ss->user_id}.stderr", [$data, $this]);
        });

        // Store the decoder
        $this->voiceDecoders[$ss->ssrc] = $decoder;

        // Monitor the process for exit
        $this->monitorProcessExit($decoder, $ss);
    }

    /**
     * Monitor a process for exit and trigger callbacks when it exits
     *
     * @param Process $process The process to monitor
     * @param object $ss The speaking status object
     * @param callable $createDecoder Function to create a new decoder if needed
     */
    protected function monitorProcessExit(Process $process, $ss): void
    {
        // Store the process ID
        // $pid = $process->getPid();

        // Check every second if the process is still running
        $this->monitorProcessTimer = $this->bot->loop->addPeriodicTimer(1.0, function () use ($process, $ss) {
            // Check if the process is still running
            if (!$process->isRunning()) {
                // Get the exit code
                $exitCode = $process->getExitCode();

                // Clean up the timer
                $this->bot->loop->cancelTimer($this->monitorProcessTimer);

                // If exit code indicates an error, emit event and recreate decoder
                if ($exitCode > 0) {
                    $this->emit('decoder-error', [$exitCode, null, $ss]);
                    unset($this->voiceDecoders[$ss->ssrc]);
                    $this->createDecoder($ss);
                }
            }
        });
    }

    /**
     * Returns whether the voice client is ready.
     *
     * @return bool Whether the voice client is ready.
     */
    public function isReady(): bool
    {
        return $this->ready;
    }

    public function getDbVolume(): float|int
    {
        return match($this->volume) {
            0 => -100,
            100 => 0,
            default => -40 + ($this->volume / 100) * 40,
        };
    }

    /**
     * Creates a new voice client instance statically
     *
     * @param \Discord\Discord $bot
     * @param \Discord\Parts\Channel\Channel $channel
     * @param array $data
     * @param bool $deaf
     * @param bool $mute
     * @param null|Deferred $deferred
     * @param null|VoiceManager $manager
     * @param bool $shouldBoot Whether the client should boot immediately.
     *
     * @return \Discord\Voice\VoiceClient
     */
    public static function make(): self
    {
        return new static(...func_get_args());
    }

    /**
     * Boots the voice client and sets up event listeners.
     *
     * @return bool
     */
    public function boot(): bool
    {
        return $this->once('ready', function () {
            $this->bot->getLogger()->info('voice client is ready');
            if (isset($this->manager->clients[$this->channel->guild_id])) {
                $this->disconnect();
            }

            $this->manager->clients[$this->channel->guild_id] = $this;

            $this->setBitrate($this->channel->bitrate);

            $this->bot->getLogger()->info('set voice client bitrate', ['bitrate' => $this->channel->bitrate]);
            $this->deferred->resolve($this);
        })
        ->once('error', function ($e) {
            $this->disconnect();
            $this->bot->getLogger()->error('error initializing voice client', ['e' => $e->getMessage()]);
            $this->deferred->reject($e);
        })
        ->once('close', function () {
            $this->disconnect();
            $this->bot->getLogger()->warning('voice client closed');
            unset($this->manager->clients[$this->channel->guild_id]);
        })
        ->start();
    }

    public function record(): void
    {
        if ($this->shouldRecord) {
            throw new \RuntimeException('Already recording audio.');
        }

        $this->shouldRecord = true;
        $this->bot->getLogger()->info('Started recording audio.');

        $this->udp->on('message', [$this, 'handleAudioData']);
    }

    public function stopRecording(): void
    {
        if (! $this->shouldRecord) {
            throw new \RuntimeException('Not recording audio.');
        }

        $this->shouldRecord = false;
        $this->bot->getLogger()->info('Stopped recording audio.');

        $this->udp->removeListener('message', [$this, 'handleAudioData']);
        $this->reset();

        foreach ($this->voiceDecoders as $decoder) {
            $decoder->close();
        }

        $this->voiceDecoders = [];
        $this->receiveStreams = [];
        $this->speakingStatus = Collection::for(VoiceSpeaking::class, 'ssrc');
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        if (isset($this->data['token'], $this->data['endpoint'], $this->data['session'], $this->data['dnsConfig'])) {
            $this->endpoint = str_replace([':80', ':443'], '', $this->data['endpoint']);
            $this->dnsConfig = $this->data['dnsConfig'];
            $this->data['user_id'] ??= $this->bot->id;
            $this->boot();
        }

        return $this;
    }
}
