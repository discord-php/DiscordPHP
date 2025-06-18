<?php

namespace Discord\Voice\Client;

use Discord\Discord;
use Discord\Helpers\ByteBuffer\Buffer;
use Discord\Parts\EventData\VoiceSpeaking;
use Discord\Parts\Voice\UserConnected;
use Discord\Voice\VoiceClient;
use Discord\WebSockets\Op;
use Discord\WebSockets\VoicePayload;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Message;
use React\ChildProcess\Process;
use React\Datagram\Factory;
use React\Datagram\Socket;
use React\Dns\Resolver\Factory as DnsFactory;
use React\Promise\PromiseInterface;

final class WS
{
    protected static VoiceClient $vc;

    protected static ?Discord $bot = null;

    protected static ?array $data = [];

    protected static WebSocket $socket;


    /**
     * The Discord voice gateway version.
     *
     * @see https://discord.com/developers/docs/topics/voice-connections#voice-gateway-versioning-gateway-versions
     *
     * @var int Voice Gateway version.
     */
    protected static $version = 8;

    public function __construct(
        VoiceClient $vc,
        ?Discord $bot = null,
        ?array $data = [],
    ) {
        self::$vc = $vc;

        if (! isset($data)) {
            self::$data = $vc->data;
        }

        if (! $bot) {
            self::$bot = $vc->bot;
        }

        $f = new Connector(self::$bot->loop);

        /** @var PromiseInterface */
        $f("wss://" . self::$data['endpoint'] . "?v=" . self::$version)
            ->then(
                static fn (WebSocket $ws) => self::handleConnection($ws),
                static fn (\Throwable $e) => self::$bot->logger->error(
                    'Failed to connect to voice gateway: {error}',
                    ['error' => $e->getMessage()]
                ) && self::$vc->emit('error', [$e])
            );
    }

    public static function make(
        VoiceClient $vc,
        ?Discord $bot = null,
        ?array $data = null
    ): self {
        return new self($vc, $bot, $data);
    }

    /**
     * Handles a WebSocket connection.
     *
     * @param WebSocket $ws The WebSocket instance.
     */
    public static function handleConnection(WebSocket $ws): void
    {
        self::$bot->logger->debug('connected to voice websocket');

        $resolver = (new DnsFactory())->createCached(self::$data['dnsConfig'], self::$bot->loop);
        $udpfac = new Factory(self::$bot->loop, $resolver);

        self::$socket = self::$vc->voiceWebsocket = $ws;

        $ip = $port = '';

        $ws->on('message', function (Message $message) use ($udpfac, &$ip, &$port): void {
            $data = json_decode($message->getPayload());
            self::$vc->emit('ws-message', [$message, self::$vc]);

            switch ($data->op) {
                case Op::VOICE_HEARTBEAT_ACK: // keepalive response
                    $end = microtime(true);
                    $start = $data->d->t;
                    $diff = ($end - $start) * 1000;

                    self::$bot->logger->debug('received heartbeat ack', ['response_time' => $diff]);
                    self::$vc->emit('ws-ping', [$diff]);
                    self::$vc->emit('ws-heartbeat-ack', [$data->d->t]);
                    break;
                case Op::VOICE_DESCRIPTION: // ready
                    self::$vc->ready = true;
                    self::$vc->mode = $data->d->mode;
                    self::$vc->secretKey = '';
                    self::$vc->rawKey = $data->d->secret_key;
                    self::$vc->secretKey = implode('', array_map(static fn ($value) => pack('C', $value), self::$vc->rawKey));

                    self::$bot->logger->debug('received description packet, vc ready', ['data' => json_decode(json_encode($data->d), true)]);

                    if (! self::$vc->reconnecting) {
                        self::$vc->emit('ready', [self::$vc]);
                    } else {
                        self::$vc->reconnecting = false;
                        self::$vc->emit('resumed', [self::$vc]);
                    }

                    if (! self::$vc->deaf && self::$vc->secretKey) {
                        self::$vc->client->on(
                            'message',
                            fn (string $message) => self::$vc->handleAudioData(new Packet(
                                $message,
                                key: self::$vc->secretKey,
                                log: self::$bot->logger
                            )));
                    }

                    break;
                case Op::VOICE_SPEAKING: // currently connected users
                    self::$bot->logger->debug('received speaking packet', ['data' => json_decode(json_encode($data->d), true)]);
                    self::$vc->emit('speaking', [$data->d->speaking, $data->d->user_id, self::$vc]);
                    self::$vc->emit("speaking.{$data->d->user_id}", [$data->d->speaking, self::$vc]);
                    self::$vc->speakingStatus[$data->d->user_id] = self::$bot->getFactory()->create(VoiceSpeaking::class, $data->d);
                    break;
                case Op::VOICE_HELLO:
                    self::$vc->heartbeatInterval = $data->d->heartbeat_interval;
                    self::sendHeartbeat();
                    self::$vc->heartbeat = self::$bot->loop->addPeriodicTimer(self::$vc->heartbeatInterval / 1000, fn () => self::sendHeartbeat());
                    break;
                case Op::VOICE_CLIENTS_CONNECT:
                    self::$bot->logger->debug('received clients connected packet', ['data' => json_decode(json_encode($data->d), true)]);
                    # "d" contains an array with ['user_ids' => array<string>]

                    self::$vc->users = array_map(fn (int $userId) => self::$bot->getFactory()->create(UserConnected::class, $userId), $data->d->user_ids);
                    break;
                case Op::VOICE_CLIENT_DISCONNECT:
                    self::$bot->logger->debug('received client disconnected packet', ['data' => json_decode(json_encode($data->d), true)]);
                    unset(self::$vc->clientsConnected[$data->d->user_id]);
                    break;
                case Op::VOICE_CLIENT_UNKNOWN_15:
                case Op::VOICE_CLIENT_UNKNOWN_18:
                    self::$bot->logger->debug('received unknown opcode', ['data' => json_decode(json_encode($data), true)]);
                    break;
                case Op::VOICE_CLIENT_PLATFORM:
                    self::$bot->logger->debug('received platform packet', ['data' => json_decode(json_encode($data->d), true)]);
                    # handlePlatformPerUser
                    # platform = 0 assumed to be Desktop
                    break;
                case Op::VOICE_DAVE_PREPARE_TRANSITION:
                    #$this->handleDavePrepareTransition($data);
                    break;
                case Op::VOICE_DAVE_EXECUTE_TRANSITION:
                    #$this->handleDaveExecuteTransition($data);
                    break;
                case Op::VOICE_DAVE_TRANSITION_READY:
                    #$this->handleDaveTransitionReady($data);
                    break;
                case Op::VOICE_DAVE_PREPARE_EPOCH:
                    #$this->handleDavePrepareEpoch($data);
                    break;
                case Op::VOICE_DAVE_MLS_EXTERNAL_SENDER:
                    #$this->handleDaveMlsExternalSender($data);
                    break;
                case Op::VOICE_DAVE_MLS_KEY_PACKAGE:
                    #$this->handleDaveMlsKeyPackage($data);
                    break;
                case Op::VOICE_DAVE_MLS_PROPOSALS:
                    #$this->handleDaveMlsProposals($data);
                    break;
                case Op::VOICE_DAVE_MLS_COMMIT_WELCOME:
                    #$this->handleDaveMlsCommitWelcome($data);
                    break;
                case Op::VOICE_DAVE_MLS_ANNOUNCE_COMMIT_TRANSITION:
                    #$this->handleDaveMlsAnnounceCommitTransition($data);
                    break;
                case Op::VOICE_DAVE_MLS_WELCOME:
                    #$this->handleDaveMlsWelcome($data);
                    break;
                case Op::VOICE_DAVE_MLS_INVALID_COMMIT_WELCOME:
                    #$this->handleDaveMlsInvalidCommitWelcome($data);
                    break;

                case Op::VOICE_READY: {
                    self::$vc->udpPort = $data->d->port;
                    self::$vc->ssrc = $data->d->ssrc;

                    self::$bot->logger->debug('received voice ready packet', ['data' => json_decode(json_encode($data->d), true)]);

                    $buffer = new Buffer(74);
                    $buffer[1] = "\x01";
                    $buffer[3] = "\x46";
                    $buffer->writeUInt32BE(self::$vc->ssrc, 4);
                    /** @var PromiseInterface */
                    $udpfac->createClient("{$data->d->ip}:" . self::$vc->udpPort)->then(function (Socket $client) use (&$ip, &$port, $buffer): void {
                        self::$bot->logger->debug('connected to voice UDP');
                        self::$vc->client = $client;

                        self::$bot->loop->addTimer(0.1, fn () => self::$vc->client->send($buffer->__toString()));

                        self::$vc->udpHeartbeat = self::$bot->loop->addPeriodicTimer(self::$vc->heartbeatInterval / 1000, function (): void {
                            $buffer = new Buffer(9);
                            $buffer[0] = 0xC9;
                            $buffer->writeUInt64LE(self::$vc->heartbeatSeq, 1);
                            ++self::$vc->heartbeatSeq;

                            self::$vc->client->send($buffer->__toString());
                            self::$vc->emit('udp-heartbeat', []);

                            self::$bot->logger->debug('sent UDP heartbeat');
                        });

                        $client->on('error', fn ($e) => self::$vc->emit('udp-error', [$e]));

                        #$client->once('message', fn ($message) => $this->decodeUDP($message, $ip, $port));
                    }, function (\Throwable $e): void {
                        self::$bot->logger->error('error while connecting to udp', ['e' => $e->getMessage()]);
                        self::$vc->emit('error', [$e]);
                    });
                    break;
                }
                default:
                    self::$bot->logger->warning('Unknown opcode.', $data);
                    break;
            }
        });

        $ws->on('error', function ($e): void {
            self::$bot->logger->error('error with voice websocket', ['e' => $e->getMessage()]);
            self::$vc->emit('ws-error', [$e]);
        });

        //$ws->on('close', [$this, 'handleClose']);


        if (self::$vc->sentLoginFrame) {
            return;
        }

        $payload = VoicePayload::new(
            Op::VOICE_IDENTIFY,
            [
                'server_id' => self::$vc->channel->guild_id,
                'user_id' => self::$data['user_id'],
                'session_id' => self::$data['session'],
                'token' => self::$data['token'],
            ],
        );

        self::$bot->logger->debug('sending identify', ['packet' => $payload->__debugInfo()]);

        self::send($payload);
        self::$vc->sentLoginFrame = true;
    }

    /**
     * Sends a message to the voice websocket.
     *
     * @param VoicePayload|array $data The data to send to the voice WebSocket.
     */
    public static function send(VoicePayload|array $data): void
    {
        $json = json_encode($data);
        self::$socket->send($json);
    }

    /**
     * Monitor a process for exit and trigger callbacks when it exits
     *
     * @param Process $process The process to monitor
     * @param object $ss The speaking status object
     * @param callable $createDecoder Function to create a new decoder if needed
     */
    /* protected function monitorProcessExit(Process $process, $ss): void
    {
        // Store the process ID
        // $pid = $process->getPid();

        // Check every second if the process is still running
        self::$monitorProcessTimer = self::$bot->loop->addPeriodicTimer(1.0, function () use ($process, $ss) {
            // Check if the process is still running
            if (!$process->isRunning()) {
                // Get the exit code
                $exitCode = $process->getExitCode();

                // Clean up the timer
                self::$bot->loop->cancelTimer($this->monitorProcessTimer);

                // If exit code indicates an error, emit event and recreate decoder
                if ($exitCode > 0) {
                    $this->emit('decoder-error', [$exitCode, null, $ss]);
                    $this->createDecoder($ss);
                }

                // Clean up temporary files
                // $this->cleanupTempFiles();
            }
        });
    } */

    protected static function handleDavePrepareTransition($data)
    {
        self::$bot->logger->debug('DAVE Prepare Transition', ['data' => $data]);
        // Prepare local state necessary to perform the transition
        self::send(VoicePayload::new(
            Op::VOICE_DAVE_TRANSITION_READY,
            [
                'transition_id' => $data->d->transition_id,
            ],
        ));
    }

    protected static function handleDaveExecuteTransition($data)
    {
        self::$bot->logger->debug('DAVE Execute Transition', ['data' => $data]);
        // Execute the transition
        // Update local state to reflect the new protocol context
    }

    protected static function handleDaveTransitionReady($data)
    {
        self::$bot->logger->debug('DAVE Transition Ready', ['data' => $data]);
        // Handle transition ready state
    }

    protected static function handleDavePrepareEpoch($data)
    {
        self::$bot->logger->debug('DAVE Prepare Epoch', ['data' => $data]);
        // Prepare local MLS group with parameters appropriate for the DAVE protocol version
        self::send(VoicePayload::new(
            Op::VOICE_DAVE_MLS_KEY_PACKAGE,
            [
                'epoch_id' => $data->d->epoch_id,
                //'key_package' => $this->generateKeyPackage(),
            ],
        ));
    }

    protected static function handleDaveMlsExternalSender($data)
    {
        self::$bot->logger->debug('DAVE MLS External Sender', ['data' => $data]);
        // Handle external sender public key and credential
    }

    protected static function handleDaveMlsKeyPackage($data)
    {
        self::$bot->logger->debug('DAVE MLS Key Package', ['data' => $data]);
        // Handle MLS key package
    }

    protected static function handleDaveMlsProposals($data)
    {
        self::$bot->logger->debug('DAVE MLS Proposals', ['data' => $data]);
        // Handle MLS proposals
        self::send(VoicePayload::new(
            Op::VOICE_DAVE_MLS_COMMIT_WELCOME,
            [
                //'commit' => $this->generateCommit(),
                //'welcome' => $this->generateWelcome(),
            ],
        ));
    }

    protected static function handleDaveMlsCommitWelcome($data)
    {
        self::$bot->logger->debug('DAVE MLS Commit Welcome', ['data' => $data]);
        // Handle MLS commit and welcome messages
    }

    protected static function handleDaveMlsAnnounceCommitTransition($data)
    {
        // Handle MLS announce commit transition
        self::$bot->logger->debug('DAVE MLS Announce Commit Transition', ['data' => $data]);
    }

    protected static function handleDaveMlsWelcome($data)
    {
        // Handle MLS welcome message
        self::$bot->logger->debug('DAVE MLS Welcome', ['data' => $data]);
    }

    protected static function handleDaveMlsInvalidCommitWelcome($data)
    {
        self::$bot->logger->debug('DAVE MLS Invalid Commit Welcome', ['data' => $data]);
        // Handle invalid commit or welcome message
        // Reset local group state and generate a new key package
        self::send(VoicePayload::new(
            Op::VOICE_DAVE_MLS_KEY_PACKAGE,
            [
                //'key_package' => $this->generateKeyPackage(),
            ],
        ));
    }

    #protected function decodeUDP($message, string &$ip, string &$port): void
    #{
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
        /* $unpackedMessageArray = \unpack("C2Type/nLength/ISSRC/A64Address/nPort", $message);

        $this->ssrc = $unpackedMessageArray['SSRC'];
        $ip = $unpackedMessageArray['Address'];
        $port = $unpackedMessageArray['Port'];

        $this->bot->logger->debug('received our IP and port', ['ip' => $ip, 'port' => $port]);

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
        ]); */
    #}

    public static function sendHeartbeat(): void
    {
        self::send(VoicePayload::new(
            Op::VOICE_HEARTBEAT,
            [
                't' => (int) microtime(true),
                'seq_ack' => 10,
            ]
        ));
        self::$bot->logger->debug('sending heartbeat');
        self::$vc->emit('ws-heartbeat', []);
    }

    // TODO still need to convert to static
    public static function handleClose(int $op, string $reason): void
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
}
