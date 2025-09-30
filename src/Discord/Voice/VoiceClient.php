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

use Discord\Exceptions\FFmpegNotFoundException;
use Discord\Exceptions\FileNotFoundException;
use Discord\Exceptions\LibSodiumNotFoundException;
use Discord\Exceptions\OutdatedDCAException;
use Discord\Helpers\Buffer as RealBuffer;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Payload;
use Discord\WebSockets\Op;
use Evenement\EventEmitter;
use Ratchet\Client\Connector as WsFactory;
use Ratchet\Client\WebSocket;
use React\Datagram\Factory as DatagramFactory;
use React\Datagram\Socket;
use React\Dns\Resolver\Factory as DNSFactory;
use React\EventLoop\LoopInterface;
use Psr\Log\LoggerInterface;
use React\ChildProcess\Process;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Stream\ReadableResourceStream as Stream;
use React\EventLoop\TimerInterface;
use React\Stream\ReadableResourceStream;
use React\Stream\ReadableStreamInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

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
    public const SILENCE_FRAME = "\xF8\xFF\xFE";

    public const SUPPORTED_MODES = [
        'aead_aes256_gcm_rtpsize',
        'aead_xchacha20_poly1305_rtpsize',
    ];

    public const DEPRECATED_MODES = [
        'xsalsa20_poly1305',
    ];

    public const UNSUPPORTED_MODES = [
        'xsalsa20_poly1305_lite_rtpsize',
        'aead_aes256_gcm',
        'xsalsa20_poly1305_suffix',
        'xsalsa20_poly1305_lite',
    ];

    public const VOICE_OP_HANDLERS = [
        Op::VOICE_READY => 'handleReady',
        Op::VOICE_SESSION_DESCRIPTION => 'handleSessionDescription',
        Op::VOICE_SPEAKING => 'handleSpeaking',
        Op::VOICE_HEARTBEAT_ACK => 'heartbeatAck',
        Op::VOICE_HELLO => 'handleHello',
        Op::VOICE_RESUMED => 'handleResumed',
        Op::VOICE_CLIENT_CONNECT => 'handleClientConnect',
        Op::VOICE_CLIENT_DISCONNECT => 'handleClientDisconnect',
        Op::VOICE_DAVE_PREPARE_TRANSITION => 'handleDavePrepareTransition',
        Op::VOICE_DAVE_EXECUTE_TRANSITION => 'handleDaveExecuteTransition',
        Op::VOICE_DAVE_TRANSITION_READY => 'handleDaveTransitionReady',
        Op::VOICE_DAVE_PREPARE_EPOCH => 'handleDavePrepareEpoch',
        Op::VOICE_DAVE_MLS_EXTERNAL_SENDER => 'handleDaveMlsExternalSender',
        Op::VOICE_DAVE_MLS_KEY_PACKAGE => 'handleDaveMlsKeyPackage',
        Op::VOICE_DAVE_MLS_PROPOSALS => 'handleDaveMlsProposals',
        Op::VOICE_DAVE_MLS_COMMIT_WELCOME => 'handleDaveMlsCommitWelcome',
        Op::VOICE_DAVE_MLS_ANNOUNCE_COMMIT_TRANSITION => 'handleDaveMlsAnnounceCommitTransition',
        Op::VOICE_DAVE_MLS_WELCOME => 'handleDaveMlsWelcome',
        Op::VOICE_DAVE_MLS_INVALID_COMMIT_WELCOME => 'handleDaveMlsInvalidCommitWelcome',
    ];

    /**
     * Is the voice client ready?
     *
     * @var bool Whether the voice client is ready.
     */
    protected $ready = false;

    /**
     * The DCA binary name that we will use.
     *
     * @var string The DCA binary name that will be run.
     */
    protected $dca;

    /**
     * The FFmpeg binary location.
     *
     * @var string
     */
    protected $ffmpeg;

    /**
     * The voice sessions.
     *
     * @var array The voice sessions.
     */
    protected $voiceSessions;

    /**
     * The ReactPHP event loop.
     *
     * @var LoopInterface The ReactPHP event loop that will run everything.
     */
    protected $loop;

    /**
     * The main WebSocket instance.
     *
     * @var WebSocket The main WebSocket client.
     */
    protected $mainWebsocket;

    /**
     * The voice WebSocket instance.
     *
     * @var WebSocket The voice WebSocket client.
     */
    protected $voiceWebsocket;

    /**
     * The UDP client.
     *
     * @var Socket The voiceUDP client.
     */
    public $client;

    /**
     * The Channel that we are connecting to.
     *
     * @var Channel The channel that we are going to connect to.
     */
    protected $channel;

    /**
     * Data from the main WebSocket.
     *
     * @var array Information required for the voice WebSocket.
     */
    protected $data;

    /**
     * The Voice WebSocket endpoint.
     *
     * @var string The endpoint the Voice WebSocket and UDP client will connect to.
     */
    protected $endpoint;

    /**
     * The Voice connection protocol.
     *
     * @var string The protocol to use for the voice connection.
     */
    protected $protocol = 'udp';

    /**
     * The IP the UDP client will use.
     *
     * @var string The IP that the UDP client will connect to.
     */
    protected $udpIp;

    /**
     * The port the UDP client will use.
     *
     * @var int The port that the UDP client will connect to.
     */
    protected $udpPort;

    /**
     * The supported transport encryption modes the voice server expects.
     *
     * @var array<string> The supported transport encryption modes.
     */
    protected $supportedModes;

    /**
     * The UDP heartbeat interval.
     *
     * @var int How often we send a heartbeat packet.
     */
    protected $heartbeat_interval;

    /**
     * The Voice WebSocket heartbeat timer.
     *
     * @var TimerInterface The heartbeat periodic timer.
     */
    protected $heartbeat;

    /**
     * The UDP heartbeat timer.
     *
     * @var TimerInterface The heartbeat periodic timer.
     */
    protected $udpHeartbeat;

    /**
     * The UDP heartbeat sequence.
     *
     * @var int The heartbeat sequence.
     */
    protected $heartbeatSeq = 0;

    /**
     * The SSRC value.
     *
     * @var int The SSRC value used for RTP.
     */
    public $ssrc;

    /**
     * The sequence of audio packets being sent.
     *
     * @var int The sequence of audio packets.
     */
    protected $seq = 0;

    /**
     * The timestamp of the last packet.
     *
     * @var int The timestamp the last packet was constructed.
     */
    protected $timestamp = 0;

    /**
     * The Voice WebSocket mode.
     *
     * @var string The transport encryption mode.
     */
    protected $mode = 'aead_aes256_gcm_rtpsize';

    /**
     * The secret key used for encrypting voice.
     *
     * @var string The secret key.
     */
    protected $secret_key;

    /**
     * Are we currently set as speaking?
     *
     * @var bool Whether we are speaking or not.
     */
    protected $speaking = false;

    /**
     * Whether we are set as mute.
     *
     * @var bool Whether we are set as mute.
     */
    protected $mute = false;

    /**
     * Whether we are set as deaf.
     *
     * @var bool Whether we are set as deaf.
     */
    protected $deaf = false;

    /**
     * Whether the voice client is currently paused.
     *
     * @var bool Whether the voice client is currently paused.
     */
    protected $paused = false;

    /**
     * Have we sent the login frame yet?
     *
     * @var bool Whether we have sent the login frame.
     */
    protected $sentLoginFrame = false;

    /**
     * The time we started sending packets.
     *
     * @var int The time we started sending packets.
     */
    protected $startTime;

    /**
     * The stream time of the last packet.
     *
     * @var int The time we sent the last packet.
     */
    protected $streamTime = 0;

    /**
     * The size of audio frames, in milliseconds.
     *
     * @var int The size of audio frames.
     */
    protected $frameSize = 20;

    /**
     * Collection of the status of people speaking.
     *
     * @var ExCollectionInterface Status of people speaking.
     */
    protected $speakingStatus;

    /**
     * Collection of voice decoders.
     *
     * @var ExCollectionInterface Voice decoders.
     */
    protected $voiceDecoders;

    /**
     * Voice audio recieve streams.
     *
     * @var array Voice audio recieve streams.
     */
    protected $recieveStreams;

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
    protected $reconnecting = false;

    /**
     * Is the voice client being closed by user?
     *
     * @var bool Whether the voice client is being closed by user.
     */
    protected $userClose = false;

    /**
     * The logger.
     *
     * @var LoggerInterface Logger.
     */
    protected $logger;

    /**
     * The Discord voice gateway version.
     *
     * @var int Voice version.
     */
    protected $version = 8;

    /**
     * The Config for DNS Resolver.
     *
     * @var string|\React\Dns\Config\Config
     */
    protected $dnsConfig;

    /**
     * Silence Frame Remain Count.
     *
     * @var int Amount of silence frames remaining.
     */
    protected $silenceRemaining = 5;

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
    protected $buffer;

    /**
     * Constructs the Voice Client instance.
     *
     * @param WebSocket       $websocket     The main WebSocket client.
     * @param LoopInterface   $loop          The ReactPHP event loop.
     * @param Channel         $channel       The channel we are connecting to.
     * @param LoggerInterface $logger        The logger.
     * @param array           $data          More information related to the voice client.
     * @param array           $voiceSessions The voice sessions.
     */
    public function __construct(WebSocket $websocket, LoopInterface $loop, Channel $channel, LoggerInterface $logger, array $data, array &$voiceSessions)
    {
        $this->voiceSessions = &$voiceSessions;
        $this->loop = $loop;
        $this->mainWebsocket = $websocket;
        $this->channel = $channel;
        $this->logger = $logger;
        $this->data = $data;
        $this->deaf = $data['deaf'];
        $this->mute = $data['mute'];
        $this->endpoint = str_replace([':80', ':443'], '', $data['endpoint']);
        $this->speakingStatus = new Collection([], 'ssrc');
        $this->dnsConfig = $data['dnsConfig'];
    }

    /**
     * Sets the transport encryption mode for the voice client.
     *
     * @param string $mode The transport encryption mode to set for the voice client.
     *
     * @throws \InvalidArgumentException If the provided mode is not supported.
     */
    public function setMode(string $mode): void
    {
        if (in_array($mode, self::SUPPORTED_MODES)) {
            $this->mode = $mode;

            return;
        }

        if (in_array($mode, self::DEPRECATED_MODES)) {
            $this->logger->warning("{$mode} is a deprecated transport encryption connection mode. Please use a supported mode: ".implode(', ', self::SUPPORTED_MODES));
            $this->mode = $mode;

            return;
        }

        $this->logger->error("{$mode} is not a supported transport encryption connection mode.");

        throw new \InvalidArgumentException("Invalid transport encryption mode: {$mode}");
    }

    /**
     * Starts the voice client.
     *
     * @return bool|void
     */
    public function start()
    {
        if (
            ! $this->checkForFFmpeg() ||
            ! $this->checkForLibsodium()
        ) {
            return false;
        }

        $this->initSockets();
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
     * Sends an identify payload to the voice gateway to authenticate the client.
     *
     * @link https://discord.com/developers/docs/topics/voice-connections#establishing-a-voice-websocket-connection-example-voice-identify-payload
     *
     * @since 10.19.0
     */
    protected function identify(): void
    {
        $data = [
            'server_id' => $this->channel->guild_id,
            'user_id' => $this->data['user_id'],
            'token' => $this->data['token'],
        ];
        if (isset($this->voiceSessions[$this->channel->guild_id])) {
            $data['session_id'] = $this->voiceSessions[$this->channel->guild_id];
        }

        $payload = Payload::new(
            Op::VOICE_IDENTIFY,
            $data
        );

        $this->logger->debug('sending identify', ['packet' => $payload]);

        $this->send($payload);
    }

    /**
     * Sends a heartbeat payload to the voice server to maintain the connection.
     *
     * @link https://discord.com/developers/docs/topics/voice-connections#heartbeating
     *
     * @since 10.19.0
     */
    protected function heartbeat(): void
    {
        $payload = Payload::new(
            Op::VOICE_HEARTBEAT,
            [
                't' => (int) microtime(true),
                'seq_ack' => $this->data['seq'] ?? -1,
            ]
        );

        $this->logger->debug('sending heartbeat', ['packet' => $payload]);

        $this->send($payload);

        $this->emit('ws-heartbeat', []);
    }

    /**
     * Handles the heartbeat acknowledgement from the voice WebSocket connection.
     *
     * @param Payload $data
     *
     * @since 10.19.0
     */
    protected function heartbeatAck($data): void
    {
        $diff = (microtime(true) - $data->d['t']) * 1000;

        $this->logger->debug('received heartbeat ack', ['response_time' => $diff]);
        $this->emit('ws-ping', [$diff]);
        $this->emit('ws-heartbeat-ack', [$data->d]);
    }

    /**
     * Handles the "Hello" event from the Discord voice server.
     *
     * @param Payload $data
     *
     * @since 10.19.0
     */
    protected function handleHello($data): void
    {
        $this->heartbeat_interval = $data->d['heartbeat_interval'];
        $this->heartbeat();
        $this->heartbeat = $this->loop->addPeriodicTimer($this->heartbeat_interval / 1000, fn () => $this->heartbeat());
    }

    /**
     * Selects the UDP protocol for the voice connection and sends the selection payload.
     *
     * @param string $ip   The IP address to use for the voice connection.
     * @param int    $port The port number to use for the voice connection.
     *
     * @since 10.19.0
     */
    protected function selectProtocol($ip, $port): void
    {
        if (! in_array($this->mode, $this->supportedModes)) {
            $this->logger->warning("{$this->mode} is not a valid transport encryption connection mode. Valid modes are: ".implode(', ', $this->supportedModes));
            $fallback = $this->supportedModes[0];
            $this->logger->info('Switching voice transport encryption mode to: '.$fallback);
            $this->mode = $fallback;
        }

        $payload = Payload::new(
            Op::VOICE_SELECT_PROTOCOL,
            [
                'protocol' => $this->protocol,
                'data' => [
                    'address' => $ip,
                    'port' => (int) $port,
                    'mode' => $this->mode,
                ],
            ]
        );

        $this->logger->debug('sending voice select protocol', ['packet' => $payload]);

        $this->send($payload);
    }

    /**
     * Handles the "ready" event for the voice client, initializing UDP connection and heartbeat.
     *
     * @param Payload $data The data object containing voice server connection details:
     *                      - $data->d['ssrc']: The synchronization source identifier.
     *                      - $data->d['ip']: The IP address for the UDP connection.
     *                      - $data->d['port']: The port for the UDP connection.
     *                      - $data->d['modes']: Supported encryption modes.
     *
     * @since 10.19.0
     */
    protected function handleReady(object $data): void
    {
        $this->ssrc = $data->d['ssrc'];
        $this->udpIp = $data->d['ip'];
        $this->udpPort = $data->d['port'];
        $this->supportedModes = $data->d['modes'];

        $this->logger->debug('received voice ready packet', ['data' => $data]);

        $udpfac = new DatagramFactory(null, (new DNSFactory())->createCached($this->dnsConfig, $this->loop));
        $udpfac->createClient("{$this->udpIp}:".$this->udpPort)->then(function (Socket $client): void {
            $this->client = $client;

            $buffer = new Buffer(74);
            $buffer[1] = "\x01";
            $buffer[3] = "\x46";
            $buffer->writeUInt32BE($this->ssrc, 4);

            $this->udpHeartbeat = $this->loop->addPeriodicTimer(5, function () {
                $buffer = new Buffer(9);
                $buffer[0] = "\xC9";
                $buffer->writeUInt64LE($this->heartbeatSeq, 1);
                ++$this->heartbeatSeq;

                $this->client->send((string) $buffer);
                $this->logger->debug('sent udp heartbeat', ['seq' => $this->heartbeatSeq]);
                $this->emit('udp-heartbeat', []);
            });

            $this->client->on('error', function (\Throwable $e): void {
                $this->logger->error('UDP error', ['e' => $e->getMessage()]);
                $this->emit('udp-error', [$e]);
            });

            $this->client->once('message', fn (string $message) => $this->decodeUDP($message));

            $this->loop->addTimer(0.1, fn () => $this->client->send((string) $buffer));
        }, function (\Throwable $e): void {
            $this->logger->error('error while connecting to udp', ['e' => $e->getMessage()]);
            $this->emit('error', [$e]);
        });
    }

    /**
     * Handles the session description packet received from the Discord voice server.
     *
     * @param Payload $data
     *
     * @since 10.19.0
     */
    protected function handleSessionDescription(object $data): void
    {
        $this->ready = true;
        $this->mode = $data->d['mode'];
        $this->secret_key = '';

        foreach ($data->d['secret_key'] as $part) {
            $this->secret_key .= pack('C*', $part);
        }

        $this->logger->debug('received description packet, vc ready', ['data' => $data]);

        if (! $this->reconnecting) {
            $this->emit('ready', [$this]);
        } else {
            $this->reconnecting = false;
            $this->emit('resumed', [$this]);
        }
    }

    /**
     * Handles the 'resumed' event for the voice client.
     *
     * @param Payload $data
     *
     * Data associated with the resumed event.
     */
    protected function handleResumed(object $data): void
    {
        $this->logger->debug('received resumed packet', ['data' => $data]);
    }

    /**
     * Handles the event when a client connects to the voice server.
     *
     * @param Payload $data
     *
     * @since 10.19.0
     */
    protected function handleClientConnect(object $data): void
    {
        $this->logger->debug('received client connect packet', ['data' => $data]);
    }

    /**
     * Handles the event when a client disconnects from the voice server.
     *
     * @param Payload $data
     *
     * @since 10.19.0
     */
    protected function handleClientDisconnect(object $data): void
    {
        $this->logger->debug('received client disconnect packet', ['data' => $data]);
    }

    protected function handleDavePrepareTransition(object $data): void
    {
        $this->logger->debug('DAVE Prepare Transition', ['data' => $data]);
        // Prepare local state necessary to perform the transition
        $this->send(Payload::new(
            Op::VOICE_DAVE_TRANSITION_READY,
            [
                'transition_id' => $data->d['transition_id'],
            ],
        ));
    }

    protected function handleDaveExecuteTransition(object $data): void
    {
        $this->logger->debug('DAVE Execute Transition', ['data' => $data]);
        // Execute the transition
        // Update local state to reflect the new protocol context
    }

    protected function handleDaveTransitionReady(object $data): void
    {
        $this->logger->debug('DAVE Transition Ready', ['data' => $data]);
        // Handle transition ready state
    }

    protected function handleDavePrepareEpoch(object $data): void
    {
        $this->logger->debug('DAVE Prepare Epoch', ['data' => $data]);
        // Prepare local MLS group with parameters appropriate for the DAVE protocol version
        $this->send(Payload::new(
            Op::VOICE_DAVE_MLS_KEY_PACKAGE,
            [
                'epoch_id' => $data->d['epoch_id'],
                'key_package' => $this->generateKeyPackage(),
            ],
        ));
    }

    protected function handleDaveMlsExternalSender(object $data)
    {
        $this->logger->debug('DAVE MLS External Sender', ['data' => $data]);
        // Handle external sender public key and credential
    }

    protected function handleDaveMlsKeyPackage(object $data): void
    {
        $this->logger->debug('DAVE MLS Key Package', ['data' => $data]);
        // Handle MLS key package
    }

    protected function handleDaveMlsProposals(object $data): void
    {
        $this->logger->debug('DAVE MLS Proposals', ['data' => $data]);
        // Handle MLS proposals
        $this->send(Payload::new(
            Op::VOICE_DAVE_MLS_COMMIT_WELCOME,
            [
                'commit' => $this->generateCommit(),
                'welcome' => $this->generateWelcome(),
            ],
        ));
    }

    protected function handleDaveMlsCommitWelcome(object $data): void
    {
        $this->logger->debug('DAVE MLS Commit Welcome', ['data' => $data]);
        // Handle MLS commit and welcome messages
    }

    protected function handleDaveMlsAnnounceCommitTransition(object $data): void
    {
        // Handle MLS announce commit transition
        $this->logger->debug('DAVE MLS Announce Commit Transition', ['data' => $data]);
    }

    protected function handleDaveMlsWelcome(object $data): void
    {
        // Handle MLS welcome message
        $this->logger->debug('DAVE MLS Welcome', ['data' => $data]);
    }

    protected function handleDaveMlsInvalidCommitWelcome(object $data): void
    {
        $this->logger->debug('DAVE MLS Invalid Commit Welcome', ['data' => $data]);
        // Handle invalid commit or welcome message
        // Reset local group state and generate a new key package
        $this->send(Payload::new(
            Op::VOICE_DAVE_MLS_KEY_PACKAGE,
            [
                'key_package' => $this->generateKeyPackage(),
            ],
        ));
    }

    /**
     * Handles the speaking state of a user.
     *
     * @param Payload $data The data object received from the WebSocket.
     */
    protected function handleSpeaking(object $data): void
    {
        $this->emit('speaking', [$data->d['speaking'], $data->d['user_id'], $this]);
        $this->emit("speaking.{$data->d['user_id']}", [$data->d['speaking'], $this]);

        $this->logger->debug('received speaking packet', ['data' => $data]);

        $this->speakingStatus[$data->d['ssrc']] = $data->d;
    }

    /**
     * Resumes a previously established voice connection.
     *
     * @since 10.19.0
     */
    protected function resume(): void
    {
        $payload = Payload::new(
            Op::VOICE_RESUME,
            [
                'server_id' => $this->channel->guild_id,
                'session_id' => $this->voiceSessions[$this->channel->guild_id],
                'token' => $this->data['token'],
                'seq_ack' => $this->data['seq'],
            ]
        );

        $this->logger->debug('sending identify', ['packet' => $payload]);

        $this->send($payload);
    }

    /**
     * Handles a WebSocket connection.
     *
     * @param WebSocket $ws The WebSocket instance.
     */
    public function handleWebSocketConnection(WebSocket $ws): void
    {
        $this->logger->debug('connected to voice websocket');

        $this->voiceWebsocket = $ws;

        $ws->on('message', function ($message) {
            if (($data = json_decode($message->getPayload(), true)) === false) {
                return;
            }
            $data = Payload::fromArray($data);

            $this->emit('ws-message', [$message, $this]);

            $this->logger->debug('received voice op', ['op' => $data->op]);
            if (isset(self::VOICE_OP_HANDLERS[$data->op])) {
                $handler = self::VOICE_OP_HANDLERS[$data->op];
                $this->$handler($data);
            } else {
                $this->logger->warning('unknown voice op', ['op' => $data->op]);
            }
        });

        $ws->on('error', function ($e) {
            $this->logger->error('error with voice websocket', ['e' => $e->getMessage()]);
            $this->emit('ws-error', [$e]);
        });

        $ws->on('close', [$this, 'handleWebSocketClose']);

        if (! $this->sentLoginFrame) {
            $this->identify();
            $this->sentLoginFrame = true;
        } elseif (isset(
            $this->data['token'],
            $this->data['seq'],
            $this->voiceSessions[$this->channel->guild_id]
        )) {
            $this->resume();
        } else {
            $this->logger->debug('existing voice session or data not found, re-sending identify', ['guild_id' => $this->channel->guild_id]);
            $this->identify();
        }
    }

    /**
     * Decodes a UDP message to extract the IP address and port, then selects the protocol for voice communication.
     *
     * @param string $message The raw UDP message received from the server.
     * @param string &$ip     Reference to a variable where the extracted IP address will be stored.
     * @param string &$port   Reference to a variable where the extracted port number will be stored.
     */
    protected function decodeUDP(string $message): void
    {
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
        $unpackedMessageArray = \unpack('C2Type/nLength/ISSRC/A64Address/nPort', (string) $message);
        $this->ssrc = $unpackedMessageArray['SSRC'] ?? -1;
        $ip = $unpackedMessageArray['Address'];
        $port = $unpackedMessageArray['Port'];

        $this->logger->debug('received our IP and port', ['ip' => $ip, 'port' => $port]);

        $this->selectProtocol($ip, $port);

        $this->client->on('message', [$this, 'handleAudioData']);
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

        // Cancel heartbeat timers
        if (isset($this->heartbeat)) {
            $this->loop->cancelTimer($this->heartbeat);
            $this->heartbeat = null;
        }

        if (isset($this->udpHeartbeat)) {
            $this->loop->cancelTimer($this->udpHeartbeat);
            $this->udpHeartbeat = null;
        }

        // Close UDP socket.
        if ($this->client) {
            $this->client->close();
        }

        // Remove voice session when leaving.
        if ($op == Op::CLOSE_VOICE_DISCONNECTED) {
            $this->logger->info('voice client disconnected from channel', ['channel_id' => $this->channel->id]);
            $this->voiceSessions[$this->channel->guild_id] = null;

            return;
        }

        // Don't reconnect on a critical opcode or if closed by user.
        if (in_array($op, Op::getCriticalVoiceCloseCodes()) || $this->userClose) {
            $this->logger->warning('received critical opcode - not reconnecting', ['op' => $op, 'reason' => $reason]);
            $this->voiceSessions[$this->channel->guild_id] = null;
            $this->emit('close');
        } else {
            $this->logger->warning('reconnecting in 2 seconds');

            // Retry connect after 2 seconds
            $this->loop->addTimer(2, function () {
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

        if (filter_var($file, FILTER_VALIDATE_URL) === false && ! file_exists($file)) {
            $deferred->reject(new FileNotFoundException("Could not find the file \"{$file}\"."));

            return $deferred->promise();
        }

        if (! $this->ready) {
            $deferred->reject(new \RuntimeException('Voice Client is not ready.'));

            return $deferred->promise();
        }

        if ($this->speaking) {
            $deferred->reject(new \RuntimeException('Audio already playing.'));

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
                if ($packet === null) {
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
            if (($metadata = json_decode($metadata, true)) !== false) {
                if (isset($metadata)) {
                    $this->frameSize = $metadata['opus']['frame_size'] / 48;
                }
            }

            $this->startTime = microtime(true) + 0.5;
            $this->readOpusTimer = $this->loop->addTimer(0.5, $readOpus);
        });

        return $deferred->promise();
    }

    /**
     * Resets the voice client.
     */
    protected function reset(): void
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
     */
    protected function sendBuffer(string $data): void
    {
        if (! $this->ready) {
            return;
        }

        $packet = new VoicePacket($data, $this->ssrc, $this->seq, $this->timestamp, true, $this->secret_key);
        $this->client->send((string) $packet);

        $this->streamTime = microtime(true);

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

        $this->send(Payload::new(
            Op::VOICE_SPEAKING,
            [
                'speaking' => $speaking,
                'delay' => 0,
            ],
        ));

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

        $this->mainSend(Payload::new(
            Op::OP_UPDATE_VOICE_STATE,
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
     * Sends a message to the voice websocket.
     *
     * @param Payload|array $data The data to send to the voice WebSocket.
     */
    protected function send(Payload|array $data): void
    {
        if (($json = json_encode($data)) !== false) {
            $this->voiceWebsocket->send($json);
        }
    }

    /**
     * Sends a message to the main websocket.
     *
     * @param Payload $data The data to send to the main WebSocket.
     */
    protected function mainSend(Payload $data): void
    {
        if (($json = json_encode($data)) !== false) {
            $this->mainWebsocket->send($json);
        }
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

        $this->mainSend(Payload::new(
            Op::OP_UPDATE_VOICE_STATE,
            [
                'guild_id' => $this->channel->guild_id,
                'channel_id' => $this->channel->id,
                'self_mute' => $mute,
                'self_deaf' => $deaf,
            ],
        ));
    }

    /**
     * Pauses the current sound.
     *
     * @throws \RuntimeException
     *
     * @return PromiseInterface
     */
    public function pause(): PromiseInterface
    {
        if (! $this->speaking) {
            return reject(new \RuntimeException('Audio must be playing to pause it.'));
        }

        if ($this->paused) {
            return reject(new \RuntimeException('Audio is already paused.'));
        }

        $this->paused = true;
        $this->silenceRemaining = 5;

        return resolve(null);
    }

    /**
     * Unpauses the current sound.
     *
     * @throws \RuntimeException
     *
     * @return PromiseInterface
     */
    public function unpause(): PromiseInterface
    {
        if (! $this->speaking) {
            return reject(new \RuntimeException('Audio must be playing to unpause it.'));
        }

        if (! $this->paused) {
            return reject(new \RuntimeException('Audio is already playing.'));
        }

        $this->paused = false;
        $this->timestamp = microtime(true) * 1000;

        return resolve(null);
    }

    /**
     * Stops the current sound.
     *
     * @throws \RuntimeException
     *
     * @return PromiseInterface
     */
    public function stop(): PromiseInterface
    {
        if (! $this->speaking) {
            return reject(new \RuntimeException('Audio must be playing to stop it.'));
        }

        $this->buffer->end();
        $this->reset();
        return $this->insertSilence();
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

        $this->mainSend(Payload::new(
            Op::OP_UPDATE_VOICE_STATE,
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

        $this->heartbeat_interval = null;

        if (isset($this->heartbeat)) {
            $this->loop->cancelTimer($this->heartbeat);
            $this->heartbeat = null;
        }

        if (isset($this->udpHeartbeat)) {
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
        }
        if ($user = $this->speakingStatus->get('user_id', $id)) {
            return $user->speaking;
        }
        if ($ssrc = $this->speakingStatus->get('ssrc', $id)) {
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
     * @param Payload $data The WebSocket data.
     */
    public function handleVoiceStateUpdate(object $data): void
    {
        if (! isset($data->d['user_id']) || ! $ss = $this->speakingStatus->get('user_id', $data->d['user_id'])) {
            return; // not in our channel
        }

        if (isset($data->d['channel_id']) && $data->d['channel_id'] == $this->channel->id) {
            return; // ignore, just a mute/deaf change
        }

        $this->removeDecoder($ss);
    }

    /**
     * Removes the voice decoder associated with the given SSRC.
     *
     * @param object $ss
     */
    protected function removeDecoder($ss)
    {
        if (! $decoder = $this->voiceDecoders[$ss->ssrc] ?? null) {
            return; // no voice decoder to remove
        }

        $decoder->close();
        unset($this->voiceDecoders[$ss->ssrc]);
        unset($this->speakingStatus[$ss->ssrc]);
    }
    /**
     * Gets a recieve voice stream.
     *
     * @param int|string $id Either a SSRC or User ID.
     *
     * @return RecieveStream
     */
    public function getRecieveStream($id): ?RecieveStream
    {
        if (isset($this->recieveStreams[$id])) {
            return $this->recieveStreams[$id];
        }

        foreach ($this->speakingStatus as $status) {
            if ($status->user_id == $id) {
                return $this->recieveStreams[$status->ssrc];
            }
        }

        return null;
    }

    /**
     * Handles raw opus data from the UDP server.
     *
     * @param string $message The data from the UDP server.
     */
    protected function handleAudioData($message): void
    {
        if ($this->deaf) {
            $this->logger->debug('ignoring voice data, client is deafened');

            return;
        }

        $this->logger->debug('received voice data', ['message' => $message]);

        $voicePacket = VoicePacket::make($message);

        if (($decrypted = $this->decryptVoicePacket($voicePacket)) === false) {
            return; // if we can't decode the message, drop it silently.
        }

        $this->emit('raw', [$decrypted, $this]);

        $vp = VoicePacket::make($voicePacket->getHeader().$decrypted);

        if (! $ss = $this->speakingStatus->get('ssrc', $vp->getSSRC())) {
            return; // for some reason we don't have a speaking status
        }

        if ($decoder = $this->voiceDecoders[$vp->getSSRC()] ?? null) {
            // make a decoder
            if (! isset($this->recieveStreams[$ss->ssrc])) {
                $this->recieveStreams[$ss->ssrc] = new RecieveStream();

                $this->recieveStreams[$ss->ssrc]->on('pcm', function ($d) {
                    $this->emit('channel-pcm', [$d, $this]);
                });

                $this->recieveStreams[$ss->ssrc]->on('opus', function ($d) {
                    $this->emit('channel-opus', [$d, $this]);
                });
            }
            $this->createDecoder($ss);
            $decoder = $this->voiceDecoders[$vp->getSSRC()] ?? null;
        }

        $buff = new Buffer(strlen($vp->getData()) + 2);
        $buff->write(pack('s', strlen($vp->getData())), 0);
        $buff->write($vp->getData(), 2);

        $decoder->stdin->write((string) $buff);
    }

    /**
     * Creates and starts a decoder process for the given stream source.
     *
     * @param object $ss The stream source object containing ssrc and user_id properties.
     */
    protected function createDecoder(object $ss)
    {
        $decoder = $this->dcaDecode();
        $decoder->start($this->loop);
        $decoder->stdout->on('data', function ($data) use ($ss) {
            $this->recieveStreams[$ss->ssrc]->writePCM($data);
        });
        $decoder->stderr->on('data', function ($data) use ($ss) {
            $this->emit("voice.{$ss->ssrc}.stderr", [$data, $this]);
            $this->emit("voice.{$ss->user_id}.stderr", [$data, $this]);
        });
        $decoder->on('exit', function ($code, $term) use ($ss) {
            if ($code > 0) {
                $this->emit('decoder-error', [$code, $term, $ss]);
                $this->createDecoder($ss);
            }
        });

        $this->voiceDecoders[$ss->ssrc] = $decoder;
    }

    protected function decryptVoicePacket(VoicePacket $voicePacket): string|false
    {
        // AEAD modes use a nonce that is a 32-bit integer appended to the payload.
        if (strpos($this->mode, 'aead') !== false) {
            $data = $voicePacket->getData();
            $nonce = str_repeat("\x00", 12); // 12-byte nonce for AES-GCM
            // The last 4 bytes of the payload are the nonce (32-bit LE integer)
            if (strlen($data) < 4) {
                return false;
            }
            $ciphertext = substr($data, 0, -4);
            $nonceInt = unpack('V', substr($data, -4))[1];
            $nonce = str_pad(pack('V', $nonceInt), 12, "\x00", STR_PAD_RIGHT);
        } else {
            $nonce = new Buffer(24);
            $nonce->write($voicePacket->getHeader(), 0);
        }

        switch ($this->mode) {
            case 'aead_aes256_gcm_rtpsize': // preferred
                return \sodium_crypto_aead_aes256gcm_decrypt(
                    $ciphertext,
                    '', // no additional data
                    (string) $nonce,
                    $this->secret_key
                );
            case 'aead_xchacha20_poly1305_rtpsize': // required
                return \sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
                    $ciphertext,
                    '', // no additional data
                    (string) $nonce,
                    $this->secret_key
                );
            case 'xsalsa20_poly1305': // deprecated
                return \sodium_crypto_secretbox_open(
                    $voicePacket->getData(),
                    (string) $nonce,
                    $this->secret_key
                );
        }

        return false;
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
     * Checks if FFmpeg is installed.
     *
     * @return bool Whether FFmpeg is installed or not.
     */
    protected function checkForFFmpeg(): bool
    {
        if ($output = $this->checkForExecutable('ffmpeg')) {
            $this->ffmpeg = $output;

            return true;
        }
        $this->emit('error', [new FFmpegNotFoundException('No FFmpeg binary was found.')]);

        return false;
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

    /**
     * Checks if an executable exists on the system.
     *
     * @param  string      $executable
     * @return string|null
     */
    protected static function checkForExecutable(string $executable): ?string
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
        $dB = match ($this->volume) {
            0 => -100,
            100 => 0,
            default => -40 + ($this->volume / 100) * 40,
        };

        $flags = [
            '-i', $filename ?? 'pipe:0',
            '-map_metadata', '-1',
            '-f', 'opus',
            '-c:a', 'libopus',
            '-ar', '48000',
            '-af', 'volume='.$dB.'dB',
            '-ac', '2',
            '-b:a', $this->bitrate,
            '-loglevel', 'warning',
            'pipe:1',
        ];

        if ($preArgs) {
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
        $flags = [
            '-ac', $channels, // Channels
            '-ab', round($this->bitrate / 1000), // Bitrate
            '-as', $frameSize ?? round($this->frameSize * 48), // Frame Size
            '-mode', 'decode', // Decode mode
        ];

        $flags = implode(' ', $flags);

        return new Process("{$this->dca} {$flags}");
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
     * Sends five frames of Opus silence to avoid unintended interpolation when there is a break in the sent data.
     *
     * @link https://discord.com/developers/docs/topics/voice-connections#voice-data-interpolation
     *
     * @return PromiseInterface Resolves after all silence frames have been sent.
     */
    protected function insertSilence(): PromiseInterface
    {
        $deferred = new Deferred();
        $this->__insertSilence($deferred);
        return $deferred->promise();
    }

    /**
     * Inserts silence frames recursively.
     *
     * @param Deferred $deferred The deferred promise to resolve when done.
     */
    protected function __insertSilence(Deferred $deferred): void
    {
        if ($this->silenceRemaining > 0) {
            $this->sendBuffer(self::SILENCE_FRAME);
            $this->silenceRemaining--;
            $this->loop->addTimer($this->frameSize / 1000, fn() => $this->__insertSilence($deferred));
        } else {
            $deferred->resolve(null);
        }
    }
}
