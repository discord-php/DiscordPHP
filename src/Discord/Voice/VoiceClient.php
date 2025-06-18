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
use Discord\Exceptions\LibSodiumNotFoundException;
use Discord\Exceptions\OutdatedDCAException;
use Discord\Exceptions\Voice\AudioAlreadyPlayingException;
use Discord\Exceptions\Voice\ClientNotReadyException;
use Discord\Helpers\Buffer as RealBuffer;
use Discord\Helpers\ByteBuffer\Buffer;
use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Channel\Channel;
use Discord\Parts\EventData\VoiceSpeaking;
use Discord\Parts\Voice\UserConnected;
use Discord\Voice\Client\Packet;
use Discord\Voice\Client\User;
use Discord\Voice\Client\WS;
use Discord\Voice\Processes\Dca;
use Discord\Voice\Processes\Ffmpeg;
use Discord\Voice\ReceiveStream;
use Discord\WebSockets\Op;
use Discord\WebSockets\Payload;
use Discord\WebSockets\VoicePayload;
use Evenement\EventEmitter;
use Ratchet\Client\Connector as WsFactory;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Message;
use React\ChildProcess\Process;
use React\Datagram\Factory as DatagramFactory;
use React\Datagram\Socket;
use React\Dns\Config\Config;
use React\Dns\Resolver\Factory as DNSFactory;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Stream\ReadableResourceStream as Stream;
use React\Stream\ReadableStreamInterface;

/**
 * The Discord voice client.
 *
 * @since 3.2.0
 */
class VoiceClient extends EventEmitter
{
    /**
     * The DCA version the client is using.
     *
     * @var string The DCA version.
     */
    public const DCA_VERSION = 'DCA1';

    /**
     * The Opus Silence Frame.
     *
     * @var string The silence frame.
     */
    public const SILENCE_FRAME = "\0xF8\0xFF\0xFE";

    /**
     * Is the voice client ready?
     *
     * @var bool Whether the voice client is ready.
     */
    public $ready = false;

    /**
     * The DCA binary name that we will use.
     *
     * @var string|null The DCA binary name that will be run.
     */
    public $dca;

    /**
     * The FFmpeg binary location.
     *
     * @var string|null The FFmpeg binary location.
     */
    public $ffmpeg;

    /**
     * The voice WebSocket instance.
     *
     * @var WebSocket|null The voice WebSocket client.
     */
    public ?WebSocket $voiceWebsocket;

    /**
     * The UDP client.
     *
     * @var Socket|null The voiceUDP client.
     */
    public $client;

    /**
     * The Voice WebSocket endpoint.
     *
     * @var string|null The endpoint the Voice WebSocket and UDP client will connect to.
     */
    public $endpoint;

    /**
     * The port the UDP client will use.
     *
     * @var int|null The port that the UDP client will connect to.
     */
    public $udpPort;

    /**
     * The UDP heartbeat interval.
     *
     * @var int|null How often we send a heartbeat packet.
     */
    public $heartbeatInterval;

    /**
     * The Voice WebSocket heartbeat timer.
     *
     * @var TimerInterface|null The heartbeat periodic timer.
     */
    public $heartbeat;

    /**
     * The UDP heartbeat timer.
     *
     * @var TimerInterface|null The heartbeat periodic timer.
     */
    public $udpHeartbeat;

    /**
     * The UDP heartbeat sequence.
     *
     * @var int The heartbeat sequence.
     */
    public $heartbeatSeq = 0;

    /**
     * The SSRC value.
     *
     * @var int|null The SSRC value used for RTP.
     */
    public $ssrc;

    /**
     * The sequence of audio packets being sent.
     *
     * @var int The sequence of audio packets.
     */
    public $seq = 0;

    /**
     * The timestamp of the last packet.
     *
     * @var int The timestamp the last packet was constructed.
     */
    public $timestamp = 0;

    /**
     * The Voice WebSocket mode.
     *
     * @link https://discord.com/developers/docs/topics/voice-connections#transport-encryption-modes
     * @var string The voice mode.
     */
    public $mode = 'aead_aes256_gcm_rtpsize';

    /**
     * The secret key used for encrypting voice.
     *
     * @var string|null The secret key.
     */
    public $secretKey;

    /**
     * The raw secret key.
     *
     * @var array|null The raw secret key.
     */
    public $rawKey;

    /**
     * Are we currently set as speaking?
     *
     * @var bool Whether we are speaking or not.
     */
    public $speaking = false;

    /**
     * Whether the voice client is currently paused.
     *
     * @var bool Whether the voice client is currently paused.
     */
    public $paused = false;

    /**
     * Have we sent the login frame yet?
     *
     * @var bool Whether we have sent the login frame.
     */
    public $sentLoginFrame = false;

    /**
     * The time we started sending packets.
     *
     * @var float|int|null The time we started sending packets.
     */
    public $startTime;

    /**
     * The stream time of the last packet.
     *
     * @var int The time we sent the last packet.
     */
    public $streamTime = 0;

    /**
     * The size of audio frames, in milliseconds.
     *
     * @var int The size of audio frames.
     */
    public $frameSize = 20;

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
    public $recieveStreams;

    /**
     * Voice audio receive streams.
     *
     * @var array<ReceiveStream>|null Voice audio recieve streams.
     */
    public $receiveStreams;

    /**
     * The volume the audio will be encoded with.
     *
     * @var int The volume that the audio will be encoded in.
     */
    protected $volume = 100;

    /**
     * The audio application to encode with.
     *
     * Available: voip, audio (default), lowdelay
     *
     * @var string The audio application.
     */
    protected $audioApplication = 'audio';

    /**
     * The bitrate to encode with.
     *
     * @var int Encoding bitrate.
     */
    protected $bitrate = 128000;

    /**
     * Is the voice client reconnecting?
     *
     * @var bool Whether the voice client is reconnecting.
     */
    public $reconnecting = false;

    /**
     * Is the voice client being closed by user?
     *
     * @var bool Whether the voice client is being closed by user.
     */
    public $userClose = false;

    /**
     * The Discord voice gateway version.
     *
     * @see https://discord.com/developers/docs/topics/voice-connections#voice-gateway-versioning-gateway-versions
     *
     * @var int Voice version.
     */
    public $version = 8;

    /**
     * The Config for DNS Resolver.
     *
     * @var Config|string|null
     */
    public $dnsConfig;

    /**
     * Silence Frame Remain Count.
     *
     * @var int Amount of silence frames remaining.
     */
    public $silenceRemaining = 5;

    /**
     * readopus Timer.
     *
     * @var TimerInterface Timer
     */
    public $readOpusTimer;

    /**
     * Audio Buffer.
     *
     * @var RealBuffer|null The Audio Buffer
     */
    public $buffer;

    /**
     * Current clients connected to the voice chat
     *
     * @var array
     */
    public array $clientsConnected = [];

    /**
     * Temporary files.
     *
     * @var array|null
     */
    public $tempFiles;

    /** @var TimerInterface */
    public $monitorProcessTimer;

    /**
     * Users in the current voice channel.
     *
     * @var array<User> Users in the current voice channel.
     */
    public array $users;

    /**
     * Constructs the Voice client instance
     *
     * @param \Discord\Discord $bot The Discord instance.
     * @param \Discord\Parts\Channel\Channel $channel
     * @param array $data
     * @param bool $deaf Default: false
     * @param bool $mute Default: false
     */
    public function __construct(
        public Discord $bot,
        public Channel $channel,
        public array $data,
        public bool $deaf = false,
        public bool $mute = false,
        protected ?Deferred $deferred = null,
        protected ?VoiceManager &$manager = null,
    ) {
        $this->deaf = $this->data['deaf'] ?? false;
        $this->mute = $this->data['mute'] ?? false;
        $this->endpoint = str_replace([':80', ':443'], '', $data['endpoint']);
        $this->speakingStatus = Collection::for(VoiceSpeaking::class, 'ssrc');
        $this->dnsConfig = $data['dnsConfig'];

        $this->boot();
    }

    /**
     * Starts the voice client.
     *
     * @return bool
     */
    public function start(): bool
    {
        if (
            ! Ffmpeg::checkForFFmpeg() ||
            ! $this->checkForLibsodium()
        ) {
            return false;
        }

        WS::make($this);
        return true;
    }


    /**
     * Handles a WebSocket close.
     *
     * @param int    $op
     * @param string $reason
     */
    public function handleWebSocketClose(int $op, string $reason): void
    {
        $this->bot->logger->warning('voice websocket closed', ['op' => $op, 'reason' => $reason]);
        $this->emit('ws-close', [$op, $reason, $this]);

        $this->clientsConnected = [];
        $this->voiceWebsocket->close();

        // Cancel heartbeat timers
        if (null !== $this->heartbeat) {
            $this->bot->loop->cancelTimer($this->heartbeat);
            $this->heartbeat = null;
        }

        if (null !== $this->udpHeartbeat) {
            $this->bot->loop->cancelTimer($this->udpHeartbeat);
            $this->udpHeartbeat = null;
        }

        // Close UDP socket.
        if (isset($this->client)) {
            $this->bot->logger->warning('closing UDP client');
            $this->client->close();
        }

        // Don't reconnect on a critical opcode or if closed by user.
        if (in_array($op, Op::getCriticalVoiceCloseCodes()) || $this->userClose) {
            $this->bot->logger->warning('received critical opcode - not reconnecting', ['op' => $op, 'reason' => $reason]);
            $this->emit('close');

            return;
        }

        if (in_array($op, [Op::CLOSE_VOICE_DISCONNECTED])) {
            $this->emit('close');

            return;
        }

        $this->bot->logger->warning('reconnecting in 2 seconds');

        // Retry connect after 2 seconds
        $this->bot->loop->addTimer(2, function (): void {
            $this->reconnecting = true;
            $this->sentLoginFrame = false;

            $this->start();
        });
    }

    /**
     * Handles a voice server change.
     *
     * @param array $data New voice server information.
     */
    public function handleVoiceServerChange(array $data = []): void
    {
        $this->bot->logger->debug('voice server has changed, dynamically changing servers in the background', ['data' => $data]);
        $this->reconnecting = true;
        $this->sentLoginFrame = false;
        $this->pause();

        $this->client->close();
        $this->voiceWebsocket->close();

        $this->bot->loop->cancelTimer($this->heartbeat);
        $this->bot->loop->cancelTimer($this->udpHeartbeat);

        $this->data['token'] = $data['token']; // set the token if it changed
        $this->endpoint = str_replace([':80', ':443'], '', $data['endpoint']);

        $this->start();

        $this->on('resumed', function () {
            $this->bot->logger->debug('voice client resumed');
            $this->unpause();
            $this->speaking = false;
            //$this->setSpeaking(true);
        });
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

        #$this->setSpeaking(true);

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
            $this->insertSilence();
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

            $this->sendBuffer($packet);

            // increment timestamp
            // uint32 overflow protection
            if (($this->timestamp += ($this->frameSize * 48)) >= 2 ** 32) {
                $this->timestamp = 0;
            }

            $nextTime = $this->startTime + (20.0 / 1000.0) * $loops;
            $delay = $nextTime - microtime(true);

            $this->readOpusTimer = $this->bot->loop->addTimer($delay, fn () => $this->readOggOpus($deferred, $ogg, $loops));
        }, function ($e) use ($deferred) {
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

        #$this->setSpeaking(true);

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
            $this->insertSilence();
            $this->readOpusTimer = $this->bot->loop->addTimer($this->frameSize / 1000, fn () => $this->readDCAOpus($deferred));

            return;
        }

        // Read opus length
        $this->buffer->readInt16(1000)->then(function ($opusLength) {
            // Read opus data
            return $this->buffer->read($opusLength, null, 1000);
        })->then(function ($opus) use ($deferred) {
            $this->sendBuffer($opus);

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

        #$this->setSpeaking(false);
        $this->streamTime = 0;
        $this->startTime = 0;
        $this->paused = false;
        $this->silenceRemaining = 5;
    }

    /**
     * Sends a buffer to the UDP socket.
     *
     * @param string $data The data to send to the UDP server.
     * @todo Fix after new change in VoicePacket
     */
    protected function sendBuffer(string $data): void
    {
        if (! $this->ready) {
            return;
        }

        $packet = new Packet($data, $this->ssrc, $this->seq, $this->timestamp, true, $this->secretKey, log: $this->bot->logger);
        $this->client->send((string) $packet);

        $this->streamTime = (int) microtime(true);

        $this->emit('packet-sent', [$packet]);
    }

    /**
     * Sets the speaking value of the client.
     *
     * @param bool $speaking Whether the client is speaking or not.
     *
     * @throws \RuntimeException
     */
    /* public function setSpeaking(bool $speaking = true): void
    {
        if ($this->speaking == $speaking) {
            return;
        }

        if (! $this->ready) {
            throw new \RuntimeException('Voice Client is not ready.');
        }

        $this->send(VoicePayload::new(
            Op::VOICE_SPEAKING,
            [
                'speaking' => $speaking,
                'delay' => 0,
                'ssrc' => $this->ssrc,
            ],
        ));

        $this->speaking = $speaking;
    } */

    /**
     * Switches voice channels.
     *
     * @param Channel $channel The channel to switch to.
     *
     * @throws \InvalidArgumentException
     */
    public function switchChannel(Channel $channel): void
    {
        if (! $channel->isVoiceBased()) {
            throw new \InvalidArgumentException("Channel must be a voice channel to be able to switch, given type {$channel->type}.");
        }

        $this->mainSend(VoicePayload::new(
            Op::OP_VOICE_STATE_UPDATE,
            [
                'guild_id' => $channel->guild_id,
                'channel_id' => $channel->id,
                'self_mute' => $this->mute,
                'self_deaf' => $this->deaf,
            ],
        ));

        $this->channel = $channel;
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
        $json = json_encode($data);
        $this->bot->ws->send($json);
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

        $this->client->removeListener('message', [$this, 'handleAudioData']);

        if (! $deaf) {
            $this->client->on('message', [$this, 'handleAudioData']);
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
        $this->silenceRemaining = 5;
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
        $this->insertSilence();
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
            #$this->setSpeaking(false);
        }

        $this->ready = false;

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
        $this->client->close();
        $this->voiceWebsocket->close();

        $this->heartbeatInterval = null;

        if (null !== $this->heartbeat) {
            $this->bot->loop->cancelTimer($this->heartbeat);
            $this->heartbeat = null;
        }

        if (null !== $this->udpHeartbeat) {
            $this->bot->loop->cancelTimer($this->udpHeartbeat);
            $this->udpHeartbeat = null;
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
        if (! isset($id)) {
            return $this->speaking;
        } elseif ($user = $this->speakingStatus->get('user_id', $id)) {
            return $user->speaking;
        } elseif ($ssrc = $this->speakingStatus->get('ssrc', $id)) {
            return $ssrc->speaking;
        }

        return false;
    }

    /**
     * Checks if we are paused.
     *
     * @return bool Whether we are paused.
     */
    public function isPaused(): bool
    {
        return $this->paused;
    }

    /**
     * Handles a voice state update.
     * NOTE: This object contains the data as the VoiceStateUpdate Part.
     * @see \Discord\Parts\WebSockets\VoiceStateUpdate
     *
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
            if ($status->user_id == $id) {
                return $this->receiveStreams[$status->ssrc];
            }
        }

        return null;
    }

    /**
     * Handles raw opus data from the UDP server.
     *
     * @param string $message The data from the UDP server.
     */
    public function handleAudioData(Packet $voicePacket): void
    {
        $message = $voicePacket?->decryptedAudio ?? null;

        if (! $message) {
            if (! $this->speakingStatus->get('ssrc', $voicePacket->getSSRC())) {
                // We don't have a speaking status for this SSRC
                // Probably a "ping" to the udp socket
                return;
            }
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
        }

        //$audioData = $decoder->stdin->write($voicePacket->getAudioData());

        /* $buff = new Buffer(strlen($audioData) + 2);
        $buff->write(pack('s', strlen($audioData)), 0);
        $buff->write($audioData, 2);

        $stdinHandle = fopen($this->tempFiles['stdin'], 'a'); // Use append mode
        fwrite($stdinHandle, (string) $buff);
        fflush($stdinHandle); // Make sure the data is written immediately
        fclose($stdinHandle); */
    }

    /**
     * Creates and initializes a decoder process for the given stream session.
     *
     * @param object $ss The stream session object containing information such as SSRC and user ID.
     */
    protected function createDecoder($ss): void
    {
        $decoder = Ffmpeg::decode();
        $decoder->start($this->bot->loop);

        $decoder->stdout->on('data', function ($data) use ($ss) {
            if (empty($data)) {
                return; // no data to process
            }

            $this->receiveStreams[$ss->ssrc]->writePCM($data);
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
        #$this->monitorProcessExit($decoder, $ss);
    }



    protected function generateKeyPackage()
    {
        // Generate and return a new MLS key package
    }

    protected function generateCommit()
    {
        // Generate and return an MLS commit message
    }

    protected function generateWelcome()
    {
        // Generate and return an MLS welcome message
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

    /**
     * Checks if libsodium-php is installed.
     *
     * @return bool
     */
    protected function checkForLibsodium(): bool
    {
        if (! function_exists('sodium_crypto_secretbox')) {
            $this->emit('error', [new LibSodiumNotFoundException('libsodium-php could not be found.')]);

            return false;
        }

        return true;
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
     * Returns the connected channel.
     *
     * @return Channel The connected channel.
     */
    public function getChannel(): Channel
    {
        return $this->channel;
    }

    /**
     * Insert 5 frames of silence.
     *
     * @link https://discord.com/developers/docs/topics/voice-connections#voice-data-interpolation
     */
    public function insertSilence(): void
    {
        while (--$this->silenceRemaining > 0) {
            $this->sendBuffer(self::SILENCE_FRAME);
        }
    }

    /**
     * Creates a new voice client instance statically
     *
     * @param \Discord\Discord $bot
     * @param \Discord\Parts\Channel\Channel $channel
     * @param array $data
     * @param bool $deaf
     * @param bool $mute
     * @param mixed $deferred
     * @param mixed $manager
     * @param array $
     * @return \Discord\Voice\VoiceClient
     */
    public static function make(
        Discord $bot,
        Channel $channel,
        array $data,
        bool $deaf = false,
        bool $mute = false,
        ?Deferred $deferred = null,
        ?VoiceManager &$manager = null,
    ): self
    {
        return new static(...func_get_args());
    }

    /**
     * Boots the voice client and sets up event listeners.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->once('ready', function () {
            $this->bot->getLogger()->info('voice client is ready');
            $this->manager->clients[$this->channel->guild_id] = $this;

            $this->setBitrate($this->channel->bitrate);

            $this->bot->getLogger()->info('set voice client bitrate', ['bitrate' => $this->channel->bitrate]);
            $this->deferred->resolve($this);
        })
        ->once('error', function ($e) {
            $this->bot->getLogger()->error('error initializing voice client', ['e' => $e->getMessage()]);
            $this->deferred->reject($e);
        })
        ->once('close', function () {
            $this->bot->getLogger()->warning('voice client closed');
            unset($this->manager->clients[$this->channel->guild_id]);
        })
        ->start();
    }
}
