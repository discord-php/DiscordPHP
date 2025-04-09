<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Voice;

use Discord\Exceptions\Voice\ClientNotReadyException;
use Discord\Exceptions\Voice\AudioAlreadyPlayingException;
use Discord\Helpers\Buffer as RealBuffer;
use Discord\Exceptions\FFmpegNotFoundException;
use Discord\Exceptions\LibSodiumNotFoundException;
use Discord\Helpers\Collection;
use Discord\Helpers\CollectionInterface;
use Discord\Exceptions\OutdatedDCAException;
use Discord\Exceptions\FileNotFoundException;
use Discord\Parts\Channel\Channel;
use Discord\Voice\VoicePacket;
use Discord\Voice\ReceiveStream;
use Discord\WebSockets\Op;
use Evenement\EventEmitter;
use Psr\Log\LoggerInterface;
use Ratchet\Client\Connector as WsFactory;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Message;
use React\ChildProcess\Process;
use React\Datagram\Factory as DatagramFactory;
use React\Datagram\Socket;
use React\Dns\Config\Config;
use React\Dns\Resolver\Factory as DNSFactory;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Stream\ReadableResourceStream as Stream;
use React\Stream\ReadableStreamInterface;
use Throwable;

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
    protected bool $ready = false;

    /**
     * The DCA binary name that we will use.
     *
     * @var string The DCA binary name that will be run.
     */
    protected ?string $dca;

    /**
     * The FFmpeg binary location.
     *
     * @var string
     */
    protected ?string $ffmpeg;

    /**
     * The voice WebSocket instance.
     *
     * @var WebSocket The voice WebSocket client.
     */
    protected ?WebSocket $voiceWebsocket;

    /**
     * The UDP client.
     *
     * @var Socket The voiceUDP client.
     */
    public ?Socket $client;

    /**
     * The Voice WebSocket endpoint.
     *
     * @var string The endpoint the Voice WebSocket and UDP client will connect to.
     */
    protected ?string $endpoint;

    /**
     * The port the UDP client will use.
     *
     * @var int The port that the UDP client will connect to.
     */
    protected ?int $udpPort;

    /**
     * The UDP heartbeat interval.
     *
     * @var int How often we send a heartbeat packet.
     */
    protected ?int $heartbeatInterval;

    /**
     * The Voice WebSocket heartbeat timer.
     *
     * @var TimerInterface|null The heartbeat periodic timer.
     */
    protected $heartbeat;

    /**
     * The UDP heartbeat timer.
     *
     * @var TimerInterface|null The heartbeat periodic timer.
     */
    protected $udpHeartbeat;

    /**
     * The UDP heartbeat sequence.
     *
     * @var int The heartbeat sequence.
     */
    protected int $heartbeatSeq = 0;

    /**
     * The SSRC value.
     *
     * @var int The SSRC value used for RTP.
     */
    public ?int $ssrc;

    /**
     * The sequence of audio packets being sent.
     *
     * @var int The sequence of audio packets.
     */
    protected int $seq = 0;

    /**
     * The timestamp of the last packet.
     *
     * @var int The timestamp the last packet was constructed.
     */
    protected int $timestamp = 0;

    /**
     * The Voice WebSocket mode.
     *
     * @link https://discord.com/developers/docs/topics/voice-connections#transport-encryption-modes
     * @var string The voice mode.
     */
    protected string $mode = 'aead_aes256_gcm_rtpsize';

    /**
     * The secret key used for encrypting voice.
     *
     * @var string The secret key.
     */
    protected ?string $secretKey;

    /**
     * The raw secret key
     *
     * @var array
     */
    protected ?array $rawKey;

    /**
     * Are we currently set as speaking?
     *
     * @var bool Whether we are speaking or not.
     */
    protected bool $speaking = false;

    /**
     * Whether the voice client is currently paused.
     *
     * @var bool Whether the voice client is currently paused.
     */
    protected bool $paused = false;

    /**
     * Have we sent the login frame yet?
     *
     * @var bool Whether we have sent the login frame.
     */
    protected bool $sentLoginFrame = false;

    /**
     * The time we started sending packets.
     *
     * @var int The time we started sending packets.
     */
    protected ?int $startTime;

    /**
     * The stream time of the last packet.
     *
     * @var int The time we sent the last packet.
     */
    protected int $streamTime = 0;

    /**
     * The size of audio frames, in milliseconds.
     *
     * @var int The size of audio frames.
     */
    protected int $frameSize = 20;

    /**
     * Collection of the status of people speaking.
     *
     * @var null|CollectionInterface Status of people speaking.
     */
    protected $speakingStatus;

    /**
     * Collection of voice decoders.
     *
     * @var null|CollectionInterface Voice decoders.
     */
    protected $voiceDecoders;

    /**
     * Voice audio recieve streams.
     *
     * @deprecated 10.5.0 Use receiveStreams instead.
     *
     * @var array<ReceiveStream> Voice audio recieve streams.
     */
    protected ?array $recieveStreams;

    /**
     * Voice audio recieve streams.
     *
     * @var array<ReceiveStream> Voice audio recieve streams.
     */
    protected ?array $receiveStreams;

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
    protected bool $reconnecting = false;

    /**
     * Is the voice client being closed by user?
     *
     * @var bool Whether the voice client is being closed by user.
     */
    protected bool $userClose = false;

    /**
     * The Discord voice gateway version.
     *
     * @see https://discord.com/developers/docs/topics/voice-connections#voice-gateway-versioning-gateway-versions
     *
     * @var int Voice version.
     */
    protected int $version = 8;

    /**
     * The Config for DNS Resolver.
     *
     * @var string|\React\Dns\Config\Config
     */
    protected null|string|Config $dnsConfig;

    /**
     * Silence Frame Remain Count.
     *
     * @var int Amount of silence frames remaining.
     */
    protected int $silenceRemaining = 5;

    /**
     * readopus Timer.
     *
     * @var TimerInterface Timer
     */
    protected $readOpusTimer;

    /**
     * Audio Buffer.
     *
     * @var RealBuffer The Audio Buffer
     */
    protected ?RealBuffer $buffer;

    /**
     * Current clients connected to the voice chat
     *
     * @var array
     */
    public array $clientsConnected = [];

    public array $tempFiles;

    /**
     * Constructs the Voice client instance
     *
     * @param \Ratchet\Client\WebSocket $mainWebsocket
     * @param \React\EventLoop\LoopInterface $loop
     * @param \Discord\Parts\Channel\Channel $channel
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     * @param bool $deaf Default: false
     * @param bool $mute Default: false
     */
    public function __construct(
        protected WebSocket $mainWebsocket,
        protected LoopInterface $loop,
        protected Channel $channel,
        protected LoggerInterface $logger,
        protected array $data,
        protected bool $deaf = false,
        protected bool $mute = false,
    ) {
        $this->deaf = $this->data['deaf'] ?? false;
        $this->mute = $this->data['mute'] ?? false;
        $this->endpoint = str_replace([':80', ':443'], '', $data['endpoint']);
        $this->speakingStatus = new Collection([], 'ssrc');
        $this->dnsConfig = $data['dnsConfig'];
    }

    /**
     * Starts the voice client.
     *
     * @return bool
     */
    public function start(): bool
    {
        if (
            ! $this->checkForFFmpeg() ||
            ! $this->checkForLibsodium()
        ) {
            return false;
        }

        $this->initSockets();
        return true;
    }

    /**
     * Initilizes the WebSocket and UDP socket.
     */
    public function initSockets(): void
    {
        $wsfac = new WsFactory($this->loop);
        /** @var PromiseInterface */
        $promise = $wsfac("wss://{$this->endpoint}?v={$this->version}");

        $promise->then([$this, 'handleWebSocketConnection'], [$this, 'handleWebSocketError']);
    }

    /**
     * Handles a WebSocket connection.
     *
     * @param WebSocket $ws The WebSocket instance.
     */
    public function handleWebSocketConnection(WebSocket $ws): void
    {
        $this->logger->debug('connected to voice websocket');

        $resolver = (new DNSFactory())->createCached($this->dnsConfig, $this->loop);
        $udpfac = new DatagramFactory($this->loop, $resolver);

        $this->voiceWebsocket = $ws;

        $ip = $port = '';

        $ws->on('message', function (Message $message) use ($udpfac, &$ip, &$port): void {
            $data = json_decode($message->getPayload());
            $this->emit('ws-message', [$message, $this]);

            switch ($data->op) {
                case Op::VOICE_HEARTBEAT_ACK: // keepalive response
                    $end = microtime(true);
                    $start = $data->d->t;
                    $diff = ($end - $start) * 1000;

                    $this->logger->debug('received heartbeat ack', ['response_time' => $diff]);
                    $this->emit('ws-ping', [$diff]);
                    $this->emit('ws-heartbeat-ack', [$data->d->t]);
                    break;
                case Op::VOICE_DESCRIPTION: // ready
                    $this->ready = true;
                    $this->mode = $data->d->mode;
                    $this->secretKey = '';
                    $this->rawKey = $data->d->secret_key;
                    $this->secretKey = implode('', array_map(fn ($value) => pack('C', $value), $this->rawKey));

                    $this->logger->debug('received description packet, vc ready', ['data' => json_decode(json_encode($data->d), true)]);

                    if (! $this->reconnecting) {
                        $this->emit('ready', [$this]);
                    } else {
                        $this->reconnecting = false;
                        $this->emit('resumed', [$this]);
                    }

                    if (! $this->deaf && $this->secretKey) {
                        $this->client->on('message', fn (string $message) => $this->handleAudioData(new VoicePacket($message, key: $this->secretKey, log: $this->logger)));
                    }

                    break;
                case Op::VOICE_SPEAKING: // currently connected users
                    $this->emit('speaking', [$data->d->speaking, $data->d->user_id, $this]);
                    $this->emit("speaking.{$data->d->user_id}", [$data->d->speaking, $this]);
                    $this->speakingStatus[$data->d->user_id] = $data->d;
                    break;
                case Op::VOICE_HELLO:
                    $this->heartbeatInterval = $data->d->heartbeat_interval;

                    $sendHeartbeat = function () {
                        $this->send([
                            'op' => Op::VOICE_HEARTBEAT,
                            'd' => [
                                't' => (int) microtime(true),
                                'seq_ack' => 10
                            ]
                        ]);
                        $this->logger->debug('sending heartbeat');
                        $this->emit('ws-heartbeat', []);
                    };

                    $sendHeartbeat();
                    $this->heartbeat = $this->loop->addPeriodicTimer($this->heartbeatInterval / 1000, $sendHeartbeat);
                    break;
                case Op::VOICE_CLIENTS_CONNECT:
                    # "d" contains an array with ['user_ids' => array<string>]
                    $this->clientsConnected = $data->d->user_ids;
                    break;
                case Op::VOICE_CLIENT_DISCONNECT:
                    unset($this->clientsConnected[$data->d->user_id]);
                    break;
                case Op::VOICE_CLIENT_UNKNOWN_15:
                case Op::VOICE_CLIENT_UNKNOWN_18:
                    $this->logger->debug('received unknown opcode', ['data' => json_decode(json_encode($data), true)]);
                    break;
                case Op::VOICE_CLIENT_PLATFORM:
                    # handlePlatformPerUser
                    # platform = 0 assumed to be Desktop
                    break;
                case Op::VOICE_DAVE_PREPARE_TRANSITION:
                    $this->handleDavePrepareTransition($data);
                    break;
                case Op::VOICE_DAVE_EXECUTE_TRANSITION:
                    $this->handleDaveExecuteTransition($data);
                    break;
                case Op::VOICE_DAVE_TRANSITION_READY:
                    $this->handleDaveTransitionReady($data);
                    break;
                case Op::VOICE_DAVE_PREPARE_EPOCH:
                    $this->handleDavePrepareEpoch($data);
                    break;
                case Op::VOICE_DAVE_MLS_EXTERNAL_SENDER:
                    $this->handleDaveMlsExternalSender($data);
                    break;
                case Op::VOICE_DAVE_MLS_KEY_PACKAGE:
                    $this->handleDaveMlsKeyPackage($data);
                    break;
                case Op::VOICE_DAVE_MLS_PROPOSALS:
                    $this->handleDaveMlsProposals($data);
                    break;
                case Op::VOICE_DAVE_MLS_COMMIT_WELCOME:
                    $this->handleDaveMlsCommitWelcome($data);
                    break;
                case Op::VOICE_DAVE_MLS_ANNOUNCE_COMMIT_TRANSITION:
                    $this->handleDaveMlsAnnounceCommitTransition($data);
                    break;
                case Op::VOICE_DAVE_MLS_WELCOME:
                    $this->handleDaveMlsWelcome($data);
                    break;
                case Op::VOICE_DAVE_MLS_INVALID_COMMIT_WELCOME:
                    $this->handleDaveMlsInvalidCommitWelcome($data);
                    break;

                case Op::VOICE_READY: {
                    $this->udpPort = $data->d->port;
                    $this->ssrc = $data->d->ssrc;

                    $this->logger->debug('received voice ready packet', ['data' => json_decode(json_encode($data->d), true)]);

                    $buffer = new Buffer(74);
                    $buffer[1] = "\x01";
                    $buffer[3] = "\x46";
                    $buffer->writeUInt32BE($this->ssrc, 4);
                    /** @var PromiseInterface */
                    $udpfac->createClient("{$data->d->ip}:{$this->udpPort}")->then(function (Socket $client) use (&$ip, &$port, $buffer): void {
                        $this->logger->debug('connected to voice UDP');
                        $this->client = $client;

                        $this->loop->addTimer(0.1, fn () => $this->client->send($buffer->__toString()));

                        $this->udpHeartbeat = $this->loop->addPeriodicTimer($this->heartbeatInterval / 1000, function (): void {
                            $buffer = new Buffer(9);
                            $buffer[0] = 0xC9;
                            $buffer->writeUInt64LE($this->heartbeatSeq, 1);
                            ++$this->heartbeatSeq;

                            $this->client->send($buffer->__toString());
                            $this->emit('udp-heartbeat', []);

                            $this->logger->debug('sent UDP heartbeat');
                        });

                        $client->on('error', fn ($e): void => $this->emit('udp-error', [$e]));

                        $decodeUDP = function ($message) use (&$ip, &$port): void {
                            /**
                             * Unpacks the message into an array.
                             *
                             * C2 (unsigned char)   | Type      | 2 bytes   | Values 0x1 and 0x2 indicate request and response, respectively
                             * n (unsigned short)   | Length    | 2 bytes   | Length of the following data
                             * I (unsigned int)     | SSRC      | 4 bytes   | The SSRC of the sender
                             * A64 (string)         | Address   | 64 bytes  | The IP address of the sender
                             * n (unsigned short)   | Port      | 2 bytes   | The port of the sender
                             *
                             * @see https://discord.com/developers/docs/topics/voice-connections#ip-discovery
                             * @see https://www.php.net/manual/en/function.unpack.php
                             * @see https://www.php.net/manual/en/function.pack.php For the formats
                             */
                            $unpackedMessageArray = \unpack("C2Type/nLength/ISSRC/A64Address/nPort", $message);

                            $this->ssrc = $unpackedMessageArray['SSRC'];
                            $ip = $unpackedMessageArray['Address'];
                            $port = $unpackedMessageArray['Port'];

                            $this->logger->debug('received our IP and port', ['ip' => $ip, 'port' => $port]);

                            $this->send([
                                'op' => Op::VOICE_SELECT_PROTO,
                                'd' => [
                                    'protocol' => 'udp',
                                    'data' => [
                                        'address' => $ip,
                                        'port' => $port,
                                        'mode' => $this->mode,
                                    ],
                                ],
                            ]);
                        };

                        $client->once('message', $decodeUDP);
                    }, function (Throwable $e): void {
                        $this->logger->error('error while connecting to udp', ['e' => $e->getMessage()]);
                        $this->emit('error', [$e]);
                    });
                    break;
                }
                default:
                    $this->logger->warning('Unknown opcode.', $data);
                    break;
            }
        });

        $ws->on('error', function ($e): void {
            $this->logger->error('error with voice websocket', ['e' => $e->getMessage()]);
            $this->emit('ws-error', [$e]);
        });

        $ws->on('close', [$this, 'handleWebSocketClose']);

        if (! $this->sentLoginFrame) {
            $payload = [
                'op' => Op::VOICE_IDENTIFY,
                'd' => [
                    'server_id' => $this->channel->guild_id,
                    'user_id' => $this->data['user_id'],
                    'session_id' => $this->data['session'],
                    'token' => $this->data['token'],
                ],
            ];

            $this->logger->debug('sending identify', ['packet' => $payload]);

            $this->send($payload);
            $this->sentLoginFrame = true;
        }
    }

    /**
     * Handles a WebSocket error.
     *
     * @param \Exception $e The error.
     */
    public function handleWebSocketError(\Exception $e): void
    {
        $this->logger->error('error with voice websocket', ['e' => $e->getMessage()]);
        $this->emit('error', [$e]);
    }

    /**
     * Handles a WebSocket close.
     *
     * @param int    $op
     * @param string $reason
     */
    public function handleWebSocketClose(int $op, string $reason): void
    {
        $this->logger->warning('voice websocket closed', ['op' => $op, 'reason' => $reason]);
        $this->emit('ws-close', [$op, $reason, $this]);

        $this->clientsConnected = [];

        // Cancel heartbeat timers
        if (null !== $this->heartbeat) {
            $this->loop->cancelTimer($this->heartbeat);
            $this->heartbeat = null;
        }

        if (null !== $this->udpHeartbeat) {
            $this->loop->cancelTimer($this->udpHeartbeat);
            $this->udpHeartbeat = null;
        }

        // Close UDP socket.
        if (isset($this->client)) {
            $this->logger->warning('closing UDP client');
            $this->client->close();
        }

        // Don't reconnect on a critical opcode or if closed by user.
        if (in_array($op, Op::getCriticalVoiceCloseCodes()) || $this->userClose) {
            $this->logger->warning('received critical opcode - not reconnecting', ['op' => $op, 'reason' => $reason]);
            $this->emit('close');
        } else {
            $this->logger->warning('reconnecting in 2 seconds');

            // Retry connect after 2 seconds
            $this->loop->addTimer(2, function (): void {
                $this->reconnecting = true;
                $this->sentLoginFrame = false;

                $this->initSockets();
            });
        }
    }

    /**
     * Handles a voice server change.
     *
     * @param array $data New voice server information.
     */
    public function handleVoiceServerChange(array $data = []): void
    {
        $this->logger->debug('voice server has changed, dynamically changing servers in the background', ['data' => $data]);
        $this->reconnecting = true;
        $this->sentLoginFrame = false;
        $this->pause();

        $this->client->close();
        $this->voiceWebsocket->close();

        $this->loop->cancelTimer($this->heartbeat);
        $this->loop->cancelTimer($this->udpHeartbeat);

        $this->data['token'] = $data['token']; // set the token if it changed
        $this->endpoint = str_replace([':80', ':443'], '', $data['endpoint']);

        $this->initSockets();

        $this->on('resumed', function () {
            $this->logger->debug('voice client resumed');
            $this->unpause();
            $this->speaking = false;
            $this->setSpeaking(true);
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

        $process = $this->ffmpegEncode($file);
        $process->start($this->loop);

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
            $stream = new Stream($stream, $this->loop);
        }

        $process = $this->ffmpegEncode(preArgs: [
            '-f', 's16le',
            '-ac', $channels,
            '-ar', $audioRate,
        ]);
        $process->start($this->loop);
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
            $stream = new ReadableResourceStream($stream, $this->loop);
        }

        if (! ($stream instanceof ReadableStreamInterface)) {
            $deferred->reject(new \InvalidArgumentException('The stream passed to playOggStream was not an instance of resource, ReactPHP Process, ReactPHP Readable Stream'));

            return $deferred->promise();
        }

        $this->buffer = new RealBuffer($this->loop);
        $stream->on('data', function ($d) {
            $this->buffer->write($d);
        });

        /** @var OggStream */
        $ogg = null;

        $loops = 0;

        $readOpus = function () use ($deferred, &$ogg, &$readOpus, &$loops) {
            $this->readOpusTimer = null;

            $loops += 1;

            // If the client is paused, delay by frame size and check again.
            if ($this->paused) {
                $this->insertSilence();
                $this->readOpusTimer = $this->loop->addTimer($this->frameSize / 1000, $readOpus);

                return;
            }

            $ogg->getPacket()->then(function ($packet) use (&$readOpus, &$loops, $deferred) {
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

                $this->readOpusTimer = $this->loop->addTimer($delay, $readOpus);
            }, function ($e) use ($deferred) {
                $this->reset();
                $deferred->resolve(null);
            });
        };

        $this->setSpeaking(true);

        OggStream::fromBuffer($this->buffer)->then(function (OggStream $os) use ($readOpus, &$ogg) {
            $ogg = $os;
            $this->startTime = microtime(true) + 0.5;
            $this->readOpusTimer = $this->loop->addTimer(0.5, $readOpus);
        });

        return $deferred->promise();
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
            $stream = new ReadableResourceStream($stream, $this->loop);
        }

        if (! ($stream instanceof ReadableStreamInterface)) {
            $deferred->reject(new \Exception('The stream passed to playDCAStream was not an instance of resource, ReactPHP Process, ReactPHP Readable Stream'));

            return $deferred->promise();
        }

        $this->buffer = new RealBuffer($this->loop);
        $stream->on('data', function ($d) {
            $this->buffer->write($d);
        });

        $readOpus = function () use ($deferred, &$readOpus) {
            $this->readOpusTimer = null;

            // If the client is paused, delay by frame size and check again.
            if ($this->paused) {
                $this->insertSilence();
                $this->readOpusTimer = $this->loop->addTimer($this->frameSize / 1000, $readOpus);

                return;
            }

            // Read opus length
            $this->buffer->readInt16(1000)->then(function ($opusLength) {
                // Read opus data
                return $this->buffer->read($opusLength, null, 1000);
            })->then(function ($opus) use (&$readOpus) {
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

                $this->readOpusTimer = $this->loop->addTimer(($this->frameSize - 1) / 1000, $readOpus);
            }, function () use ($deferred) {
                $this->reset();
                $deferred->resolve(null);
            });
        };

        $this->setSpeaking(true);

        // Read magic byte header
        $this->buffer->read(4)->then(function ($mb) {
            if ($mb !== self::DCA_VERSION) {
                throw new OutdatedDCAException('The DCA magic byte header was not correct.');
            }

            // Read JSON length
            return $this->buffer->readInt32();
        })->then(function ($jsonLength) {
            // Read JSON content
            return $this->buffer->read($jsonLength);
        })->then(function ($metadata) use ($readOpus) {
            $metadata = json_decode($metadata, true);

            if (null !== $metadata) {
                $this->frameSize = $metadata['opus']['frame_size'] / 48;
            }

            $this->startTime = microtime(true) + 0.5;
            $this->readOpusTimer = $this->loop->addTimer(0.5, $readOpus);
        });

        return $deferred->promise();
    }

    /**
     * Resets the voice client.
     */
    private function reset(): void
    {
        if ($this->readOpusTimer) {
            $this->loop->cancelTimer($this->readOpusTimer);
            $this->readOpusTimer = null;
        }

        $this->setSpeaking(false);
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
    private function sendBuffer(string $data): void
    {
        if (! $this->ready) {
            return;
        }

        $packet = new VoicePacket($data, $this->ssrc, $this->seq, $this->timestamp, true, $this->secretKey);
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
    public function setSpeaking(bool $speaking = true): void
    {
        if ($this->speaking == $speaking) {
            return;
        }

        if (! $this->ready) {
            throw new \RuntimeException('Voice Client is not ready.');
        }

        $this->send([
            'op' => Op::VOICE_SPEAKING,
            'd' => [
                'speaking' => $speaking,
                'delay' => 0,
                'ssrc' => $this->ssrc,
            ],
        ]);

        $this->speaking = $speaking;
    }

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

        $this->mainSend([
            'op' => Op::OP_VOICE_STATE_UPDATE,
            'd' => [
                'guild_id' => $channel->guild_id,
                'channel_id' => $channel->id,
                'self_mute' => $this->mute,
                'self_deaf' => $this->deaf,
            ],
        ]);

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
     * Sends a message to the voice websocket.
     *
     * @param array $data The data to send to the voice WebSocket.
     */
    private function send(array $data): void
    {
        $json = json_encode($data);
        $this->voiceWebsocket->send($json);
    }

    /**
     * Sends a message to the main websocket.
     *
     * @param array $data The data to send to the main WebSocket.
     */
    private function mainSend(array $data): void
    {
        $json = json_encode($data);
        $this->mainWebsocket->send($json);
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

        $this->mainSend([
            'op' => Op::OP_VOICE_STATE_UPDATE,
            'd' => [
                'guild_id' => $this->channel->guild_id,
                'channel_id' => $this->channel->id,
                'self_mute' => $mute,
                'self_deaf' => $deaf,
            ],
        ]);

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
            $this->setSpeaking(false);
        }

        $this->ready = false;

        $this->mainSend([
            'op' => Op::OP_VOICE_STATE_UPDATE,
            'd' => [
                'guild_id' => $this->channel->guild_id,
                'channel_id' => null,
                'self_mute' => true,
                'self_deaf' => true,
            ],
        ]);

        $this->userClose = true;
        $this->client->close();
        $this->voiceWebsocket->close();

        $this->heartbeatInterval = null;

        if (null !== $this->heartbeat) {
            $this->loop->cancelTimer($this->heartbeat);
            $this->heartbeat = null;
        }

        if (null !== $this->udpHeartbeat) {
            $this->loop->cancelTimer($this->udpHeartbeat);
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
     *
     * @param object $data The WebSocket data.
     */
    public function handleVoiceStateUpdate(object $data): void
    {
        $removeDecoder = function ($ss) {
            $decoder = $this->voiceDecoders[$ss->ssrc] ?? null;

            if (null === $decoder) {
                return; // no voice decoder to remove
            }

            $decoder->close();
            unset($this->voiceDecoders[$ss->ssrc]);
            unset($this->speakingStatus[$ss->ssrc]);
        };

        $ss = $this->speakingStatus->get('user_id', $data->user_id);

        if (null === $ss) {
            return; // not in our channel
        }

        if ($data->channel_id == $this->channel->id) {
            return; // ignore, just a mute/deaf change
        }

        $removeDecoder($ss);
    }

    /**
     * Gets a recieve voice stream.
     *
     * @param int|string $id Either a SSRC or User ID.
     *
     * @deprecated 10.5.0 Use getReceiveStream instead.
     *
     * @return null|RecieveStream|ReceiveStream
     */
    public function getRecieveStream($id): null|RecieveStream|ReceiveStream
    {
        return $this->getReceiveStream($id);
    }

    /**
     * Gets a receive voice stream.
     *
     * @param int|string $id Either a SSRC or User ID.
     *
     * @return null|ReceiveStream
     */
    public function getReceiveStream($id): null|ReceiveStream
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
    protected function handleAudioData(VoicePacket $voicePacket): void
    {
        $message = $voicePacket?->decryptedAudio ?? null;

        if (! $message) {
            if (! $this->speakingStatus->get('ssrc', $voicePacket->getSSRC())) {
                // We don't have a speaking status for this SSRC
                // Probably a "ping" to the udp socket
                return;
            }
            // There's no message or the message threw an error inside the decrypt function
            $this->logger->warning('No audio data.', ['voicePacket' => $voicePacket]);
            return;
        }

        $this->emit('raw', [$message, $this]);

        $ss = $this->speakingStatus->get('ssrc', $voicePacket->getSSRC());
        $decoder = $this->voiceDecoders[$voicePacket->getSSRC()] ?? null;

        if (null === $ss) {
            // for some reason we don't have a speaking status
            $this->logger->warning('Unknown SSRC.', ['ssrc' => $voicePacket->getSSRC(), 't' => $voicePacket->getTimestamp()]);
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

            $createDecoder = function () use (&$createDecoder, $ss) {
                $decoder = $this->ffmpegDecode();
                $decoder->start($this->loop);

                // Handle stdout
                $stdoutHandle = fopen($this->tempFiles['stdout'], 'r');
                $this->loop->addPeriodicTimer(0.1, function () use ($stdoutHandle, $ss) {
                    $data = fread($stdoutHandle, 8192);
                    if ($data) {
                        $this->receiveStreams[$ss->ssrc]->writePCM($data);
                    }
                });

                // Handle stderr
                $stderrHandle = fopen($this->tempFiles['stderr'], 'r');
                $this->loop->addPeriodicTimer(0.1, function () use ($stderrHandle, $ss) {
                    $data = fread($stderrHandle, 8192);
                    if ($data) {
                        $this->emit("voice.{$ss->ssrc}.stderr", [$data, $this]);
                        $this->emit("voice.{$ss->user_id}.stderr", [$data, $this]);
                    }
                });

                // Store the decoder
                $this->voiceDecoders[$ss->ssrc] = $decoder;

                // Monitor the process for exit
                $this->monitorProcessExit($decoder, $ss, $createDecoder);
            };

            $createDecoder();
            $decoder = $this->voiceDecoders[$voicePacket->getSSRC()] ?? null;
        }

        $audioData = $voicePacket->getAudioData();

        $buff = new Buffer(strlen($audioData) + 2);
        $buff->write(pack('s', strlen($audioData)), 0);
        $buff->write($audioData, 2);

        $stdinHandle = fopen($this->tempFiles['stdin'], 'a'); // Use append mode
        fwrite($stdinHandle, (string) $buff);
        fflush($stdinHandle); // Make sure the data is written immediately
        fclose($stdinHandle);
    }

    /**
     * Monitor a process for exit and trigger callbacks when it exits
     *
     * @param Process $process The process to monitor
     * @param object $ss The speaking status object
     * @param callable $createDecoder Function to create a new decoder if needed
     */
    private function monitorProcessExit(Process $process, $ss, callable $createDecoder): void
    {
        // Store the process ID
        // $pid = $process->getPid();

        // Check every second if the process is still running
        $timer = $this->loop->addPeriodicTimer(1.0, function () use ($process, $ss, &$createDecoder, &$timer) {
            // Check if the process is still running
            if (!$process->isRunning()) {
                // Get the exit code
                $exitCode = $process->getExitCode();

                // Clean up the timer
                $this->loop->cancelTimer($timer);

                // If exit code indicates an error, emit event and recreate decoder
                if ($exitCode > 0) {
                    $this->emit('decoder-error', [$exitCode, null, $ss]);
                    $createDecoder();
                }

                // Clean up temporary files
                $this->cleanupTempFiles();
            }
        });
    }

    private function cleanupTempFiles(): void
    {
        if (isset($this->tempFiles)) {
            foreach ($this->tempFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    private function handleDavePrepareTransition($data)
    {
        $this->logger->debug('DAVE Prepare Transition', ['data' => $data]);
        // Prepare local state necessary to perform the transition
        $this->send([
            'op' => Op::VOICE_DAVE_TRANSITION_READY,
            'd' => [
                'transition_id' => $data->d->transition_id,
            ],
        ]);
    }

    private function handleDaveExecuteTransition($data)
    {
        $this->logger->debug('DAVE Execute Transition', ['data' => $data]);
        // Execute the transition
        // Update local state to reflect the new protocol context
    }

    private function handleDaveTransitionReady($data)
    {
        $this->logger->debug('DAVE Transition Ready', ['data' => $data]);
        // Handle transition ready state
    }

    private function handleDavePrepareEpoch($data)
    {
        $this->logger->debug('DAVE Prepare Epoch', ['data' => $data]);
        // Prepare local MLS group with parameters appropriate for the DAVE protocol version
        $this->send([
            'op' => Op::VOICE_DAVE_MLS_KEY_PACKAGE,
            'd' => [
                'epoch_id' => $data->d->epoch_id,
                'key_package' => $this->generateKeyPackage(),
            ],
        ]);
    }

    private function handleDaveMlsExternalSender($data)
    {
        $this->logger->debug('DAVE MLS External Sender', ['data' => $data]);
        // Handle external sender public key and credential
    }

    private function handleDaveMlsKeyPackage($data)
    {
        $this->logger->debug('DAVE MLS Key Package', ['data' => $data]);
        // Handle MLS key package
    }

    private function handleDaveMlsProposals($data)
    {
        $this->logger->debug('DAVE MLS Proposals', ['data' => $data]);
        // Handle MLS proposals
        $this->send([
            'op' => Op::VOICE_DAVE_MLS_COMMIT_WELCOME,
            'd' => [
                'commit' => $this->generateCommit(),
                'welcome' => $this->generateWelcome(),
            ],
        ]);
    }

    private function handleDaveMlsCommitWelcome($data)
    {
        $this->logger->debug('DAVE MLS Commit Welcome', ['data' => $data]);
        // Handle MLS commit and welcome messages
    }

    private function handleDaveMlsAnnounceCommitTransition($data)
    {
        // Handle MLS announce commit transition
        $this->logger->debug('DAVE MLS Announce Commit Transition', ['data' => $data]);
    }

    private function handleDaveMlsWelcome($data)
    {
        // Handle MLS welcome message
        $this->logger->debug('DAVE MLS Welcome', ['data' => $data]);
    }

    private function handleDaveMlsInvalidCommitWelcome($data)
    {
        $this->logger->debug('DAVE MLS Invalid Commit Welcome', ['data' => $data]);
        // Handle invalid commit or welcome message
        // Reset local group state and generate a new key package
        $this->send([
            'op' => Op::VOICE_DAVE_MLS_KEY_PACKAGE,
            'd' => [
                'key_package' => $this->generateKeyPackage(),
            ],
        ]);
    }

    private function generateKeyPackage()
    {
        // Generate and return a new MLS key package
    }

    private function generateCommit()
    {
        // Generate and return an MLS commit message
    }

    private function generateWelcome()
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
     * Checks if FFmpeg is installed.
     *
     * @return bool Whether FFmpeg is installed or not.
     */
    private function checkForFFmpeg(): bool
    {
        $binaries = [
            'ffmpeg',
        ];

        foreach ($binaries as $binary) {
            $output = $this->checkForExecutable($binary);

            if (null !== $output) {
                $this->ffmpeg = $output;

                return true;
            }
        }

        $this->emit('error', [new FFmpegNotFoundException('No FFmpeg binary was found.')]);

        return false;
    }

    /**
     * Checks if libsodium-php is installed.
     *
     * @return bool
     */
    private function checkForLibsodium(): bool
    {
        if (! function_exists('sodium_crypto_secretbox')) {
            $this->emit('error', [new LibSodiumNotFoundException('libsodium-php could not be found.')]);

            return false;
        }

        return true;
    }

    /**
     * Checks if an executable exists on the system.
     *
     * @param  string      $executable
     * @return string|null
     */
    private static function checkForExecutable(string $executable): ?string
    {
        $which = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'command -v';
        $executable = rtrim((string) explode(PHP_EOL, shell_exec("{$which} {$executable}"))[0]);

        return is_executable($executable) ? $executable : null;
    }

    /**
     * Creates a process that will run FFmpeg and encode `$filename` into Ogg
     * Opus format.
     *
     * If `$filename` is null, the process will expect some sort of audio data
     * to be piped in via stdin. It is highly recommended to set `$preArgs` to
     * contain the format of the piped data when using a pipe as an input. You
     * may also want to provide some arguments to FFmpeg via `$preArgs`, which
     * will be appended to the FFmpeg command _before_ setting the input
     * arguments.
     *
     * @param ?string $filename Path to file to be converted into Ogg Opus, or
     *                          null for pipe via stdin.
     * @param ?array  $preArgs  A list of arguments to be appended before the
     *                          input filename.
     *
     * @return Process A ReactPHP child process.
     */
    public function ffmpegEncode(?string $filename = null, ?array $preArgs = null): Process
    {
        $flags = [
            '-i', $filename ?? 'pipe:0',
            '-map_metadata', '-1',
            '-f', 'opus',
            '-c:a', 'libopus',
            '-ar', '48000',
            '-ac', '2',
            '-b:a', $this->bitrate,
            '-loglevel', 'warning',
            'pipe:1',
        ];

        if (null !== $preArgs) {
            $flags = array_merge($preArgs, $flags);
        }

        $flags = implode(' ', $flags);
        $cmd = "{$this->ffmpeg} {$flags}";

        return new Process($cmd, null, null, [
            ['socket'],
            ['socket'],
            ['socket'],
        ]);
    }

    /**
     * Decodes a file from Opus with DCA.
     *
     * @param int      $channels  How many audio channels to decode with.
     * @param int|null $frameSize The Opus packet frame size.
     *
     * @return Process A ReactPHP Child Process
     */
    public function dcaDecode(int $channels = 2, ?int $frameSize = null): Process
    {
        if (null === $frameSize) {
            $frameSize = round($this->frameSize * 48);
        }

        $flags = [
            '-ac', $channels, // Channels
            '-ab', round($this->bitrate / 1000), // Bitrate
            '-as', $frameSize, // Frame Size
            '-mode', 'decode', // Decode mode
        ];

        $flags = implode(' ', $flags);

        return new Process("{$this->dca} {$flags}");
    }

    public function ffmpegDecode(int $channels = 2, ?int $frameSize = null): Process
    {
        if (null === $frameSize) {
            $frameSize = round($this->frameSize * 48);
        }

        $flags = [
            '-ac:opus', $channels, // Channels
            '-ab', round($this->bitrate / 1000), // Bitrate
            '-as', $frameSize, // Frame Size
            '-ar', '48000', // Audio Rate
            '-mode', 'decode', // Decode mode
        ];

        $flags = implode(' ', $flags);

        // Create temporary files for stdin, stdout, and stderr
        $tempDir = sys_get_temp_dir();
        $stdinFile = tempnam($tempDir, 'discord_ffmpeg_stdin_' . $this->ssrc);
        $stdoutFile = tempnam($tempDir, 'discord_ffmpeg_stdout_' . $this->ssrc);
        $stderrFile = tempnam($tempDir, 'discord_ffmpeg_stderr_' . $this->ssrc);

        // Store temp file paths for later cleanup
        $this->tempFiles = [
            'stdin' => $stdinFile,
            'stdout' => $stdoutFile,
            'stderr' => $stderrFile,
        ];

        return new Process(
            "{$this->ffmpeg} {$flags}",
            fds: [
                ['file', $stdinFile, 'w'],
                ['file', $stdoutFile, 'w+'],
                ['file', $stderrFile, 'w+'],
            ]
        );
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

}
