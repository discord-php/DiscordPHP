<?php

declare(strict_types=1);

namespace Discord\Voice\Client;

use Discord\Discord;
use Discord\Factory\SocketFactory;
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
use function Discord\logger;

final class WS
{
    protected WebSocket $socket;

    /**
     * The Discord voice gateway version.
     *
     * @see https://discord.com/developers/docs/topics/voice-connections#voice-gateway-versioning-gateway-versions
     *
     * @var int Voice Gateway version.
     */
    protected static $version = 8;

    /**
     * The Voice WebSocket mode.
     *
     * @link https://discord.com/developers/docs/topics/voice-connections#transport-encryption-modes
     */
    public string $mode = 'aead_aes256_gcm_rtpsize';

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

    public $ssrc;

    public function __construct(
        public VoiceClient $vc,
        protected ?Discord $bot = null,
        protected ?array $data = [],
    ) {
        $this->data ??= $this->vc->data;
        $this->bot ??= $this->vc->bot;

        $f = new Connector($this->bot->loop);

        /** @var PromiseInterface */
        $f("wss://" . $this->data['endpoint'] . "?v=" . self::$version)
            ->then(
                fn (WebSocket $ws) => $this->handleConnection($ws),
                fn (\Throwable $e) => $this->bot->logger->error(
                    'Failed to connect to voice gateway: {error}',
                    ['error' => $e->getMessage()]
                ) && $this->vc->emit('error', [$e])
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
    public function handleConnection(WebSocket $ws): void
    {
        $this->bot->logger->debug('connected to voice websocket');

        $resolver = (new DnsFactory())->createCached($this->data['dnsConfig'], $this->bot->loop);
        $udpfac = new SocketFactory($this->bot->loop, $resolver, $this);

        $this->socket = $this->vc->voiceWebsocket = $ws;

        $ip = $port = '';

        $ws->on('message', function (Message $message) use ($udpfac): void {
            $data = json_decode($message->getPayload());
            $this->vc->emit('ws-message', [$message, $this->vc]);

            switch ($data->op) {
                case Op::VOICE_HEARTBEAT_ACK: // keepalive response
                    $end = microtime(true);
                    $start = $data->d->t;
                    $diff = ($end - $start) * 1000;

                    $this->bot->logger->debug('received heartbeat ack', ['response_time' => $diff]);
                    $this->vc->emit('ws-ping', [$diff]);
                    $this->vc->emit('ws-heartbeat-ack', [$data->d->t]);
                    break;
                case Op::VOICE_DESCRIPTION: // ready
                    $this->vc->ready = true;
                    $this->mode = $data->d->mode === $this->mode ? $this->mode : 'aead_aes256_gcm_rtpsize';
                    $this->secretKey = '';
                    $this->rawKey = $data->d->secret_key;
                    $this->secretKey = implode('', array_map(static fn ($value) => pack('C', $value), $this->rawKey));

                    $this->bot->logger->debug('received description packet, vc ready', ['data' => json_decode(json_encode($data->d), true)]);

                    if (! $this->vc->reconnecting) {
                        $this->vc->emit('ready', [$this->vc]);
                    } else {
                        $this->vc->reconnecting = false;
                        $this->vc->emit('resumed', [$this->vc]);
                    }

                    if (! $this->vc->deaf && $this->secretKey) {
                        $this->vc->client->handleMessages($this->vc, $this->secretKey);
                    }

                    break;
                case Op::VOICE_SPEAKING: // currently connected users
                    $this->bot->logger->debug('received speaking packet', ['data' => json_decode(json_encode($data->d), true)]);
                    $this->vc->emit('speaking', [$data->d->speaking, $data->d->user_id, $this->vc]);
                    $this->vc->emit("speaking.{$data->d->user_id}", [$data->d->speaking, $this->vc]);
                    $this->vc->speakingStatus[$data->d->user_id] = $this->bot->getFactory()->create(VoiceSpeaking::class, $data->d);
                    break;
                case Op::VOICE_HELLO:
                    $this->vc->heartbeatInterval = $data->d->heartbeat_interval;
                    $this->sendHeartbeat();
                    $this->vc->heartbeat = $this->bot->loop->addPeriodicTimer($this->vc->heartbeatInterval / 1000, fn () => $this->sendHeartbeat());
                    break;
                case Op::VOICE_CLIENTS_CONNECT:
                    $this->bot->logger->debug('received clients connected packet', ['data' => json_decode(json_encode($data->d), true)]);
                    # "d" contains an array with ['user_ids' => array<string>]

                    $this->vc->users = array_map(fn (int $userId) => $this->bot->getFactory()->create(UserConnected::class, $userId), $data->d->user_ids);
                    break;
                case Op::VOICE_CLIENT_DISCONNECT:
                    $this->bot->logger->debug('received client disconnected packet', ['data' => json_decode(json_encode($data->d), true)]);
                    unset($this->vc->clientsConnected[$data->d->user_id]);
                    break;
                case Op::VOICE_CLIENT_UNKNOWN_15:
                case Op::VOICE_CLIENT_UNKNOWN_18:
                    $this->bot->logger->debug('received unknown opcode', ['data' => json_decode(json_encode($data), true)]);
                    break;
                case Op::VOICE_CLIENT_PLATFORM:
                    $this->bot->logger->debug('received platform packet', ['data' => json_decode(json_encode($data->d), true)]);
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
                    $this->vc->udpPort = $data->d->port;
                    $this->vc->ssrc = $data->d->ssrc;

                    $this->bot->logger->debug('received voice ready packet', ['data' => json_decode(json_encode($data->d), true)]);

                    /** @var PromiseInterface */
                    $udpfac->createClient("{$data->d->ip}:" . $this->vc->udpPort)->then(function (UDP $client): void {
                        $this->vc->client = $client;
                        $client->handleSsrcSending()
                            ->handleHeartbeat()
                            ->handleErrors()
                            ->decodeOnce();
                    }, function (\Throwable $e): void {
                        $this->bot->logger->error('error while connecting to udp', ['e' => $e->getMessage()]);
                        $this->vc->emit('error', [$e]);
                    });
                    break;
                }
                default:
                    $this->bot->logger->warning('Unknown opcode.', $data);
                    break;
            }
        });

        $ws->on('error', function ($e): void {
            $this->bot->logger->error('error with voice websocket', ['e' => $e->getMessage()]);
            $this->vc->emit('ws-error', [$e]);
        });

        $ws->on('close', [$this, 'handleClose']);


        if ($this->vc->sentLoginFrame) {
            return;
        }

        $payload = VoicePayload::new(
            Op::VOICE_IDENTIFY,
            [
                'server_id' => $this->vc->channel->guild_id,
                'user_id' => $this->data['user_id'],
                'session_id' => $this->data['session'],
                'token' => $this->data['token'],
            ],
        );

        $this->bot->logger->debug('sending identify', ['packet' => $payload->__debugInfo()]);

        $this->send($payload);
        $this->vc->sentLoginFrame = true;
    }

    /**
     * Sends a message to the voice websocket.
     *
     * @param VoicePayload|array $data The data to send to the voice WebSocket.
     */
    public function send(VoicePayload|array $data): void
    {
        $json = json_encode($data);
        $this->socket->send($json);
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
                    $this->vc->emit('decoder-error', [$exitCode, null, $ss]);
                    //$this->createDecoder($ss);
                }

                // Clean up temporary files
                // $this->cleanupTempFiles();
            }
        });
    }

    protected function handleDavePrepareTransition($data): void
    {
        $this->bot->logger->debug('DAVE Prepare Transition', ['data' => $data]);
        // Prepare local state necessary to perform the transition
        $this->send(VoicePayload::new(
            Op::VOICE_DAVE_TRANSITION_READY,
            [
                'transition_id' => $data->d->transition_id,
            ],
        ));
    }

    protected function handleDaveExecuteTransition($data): void
    {
        $this->bot->logger->debug('DAVE Execute Transition', ['data' => $data]);
        // Execute the transition
        // Update local state to reflect the new protocol context
    }

    protected function handleDaveTransitionReady($data): void
    {
        $this->bot->logger->debug('DAVE Transition Ready', ['data' => $data]);
        // Handle transition ready state
    }

    protected function handleDavePrepareEpoch($data): void
    {
        $this->bot->logger->debug('DAVE Prepare Epoch', ['data' => $data]);
        // Prepare local MLS group with parameters appropriate for the DAVE protocol version
        $this->send(VoicePayload::new(
            Op::VOICE_DAVE_MLS_KEY_PACKAGE,
            [
                'epoch_id' => $data->d->epoch_id,
                //'key_package' => $this->generateKeyPackage(),
            ],
        ));
    }

    protected function handleDaveMlsExternalSender($data): void
    {
        $this->bot->logger->debug('DAVE MLS External Sender', ['data' => $data]);
        // Handle external sender public key and credential
    }

    protected function handleDaveMlsKeyPackage($data): void
    {
        $this->bot->logger->debug('DAVE MLS Key Package', ['data' => $data]);
        // Handle MLS key package
    }

    protected function handleDaveMlsProposals($data): void
    {
        $this->bot->logger->debug('DAVE MLS Proposals', ['data' => $data]);
        // Handle MLS proposals
        $this->send(VoicePayload::new(
            Op::VOICE_DAVE_MLS_COMMIT_WELCOME,
            [
                //'commit' => $this->generateCommit(),
                //'welcome' => $this->generateWelcome(),
            ],
        ));
    }

    protected function handleDaveMlsCommitWelcome($data): void
    {
        $this->bot->logger->debug('DAVE MLS Commit Welcome', ['data' => $data]);
        // Handle MLS commit and welcome messages
    }

    protected function handleDaveMlsAnnounceCommitTransition($data)
    {
        // Handle MLS announce commit transition
        $this->bot->logger->debug('DAVE MLS Announce Commit Transition', ['data' => $data]);
    }

    protected function handleDaveMlsWelcome($data)
    {
        // Handle MLS welcome message
        $this->bot->logger->debug('DAVE MLS Welcome', ['data' => $data]);
    }

    protected function handleDaveMlsInvalidCommitWelcome($data)
    {
        $this->bot->logger->debug('DAVE MLS Invalid Commit Welcome', ['data' => $data]);
        // Handle invalid commit or welcome message
        // Reset local group state and generate a new key package
        $this->send(VoicePayload::new(
            Op::VOICE_DAVE_MLS_KEY_PACKAGE,
            [
                //'key_package' => $this->generateKeyPackage(),
            ],
        ));
    }

    public function sendHeartbeat(): void
    {
        $this->send(VoicePayload::new(
            Op::VOICE_HEARTBEAT,
            [
                't' => (int) microtime(true),
                'seq_ack' => 10,
            ]
        ));
        $this->bot->logger->debug('sending heartbeat');
        $this->vc->emit('ws-heartbeat', []);
    }

    public function handleClose(int $op, string $reason): void
    {
        $this->bot->logger->warning('voice websocket closed', ['op' => $op, 'reason' => $reason]);
        $this->vc->emit('ws-close', [$op, $reason, $this]);

        $this->clientsConnected = [];
        $this->socket->close();

        // Cancel heartbeat timers
        if (null !== $this->vc->heartbeat) {
            $this->bot->loop->cancelTimer($this->vc->heartbeat);
            $this->heartbeat = null;
        }

        if (null !== $this->vc->udpHeartbeat) {
            $this->bot->loop->cancelTimer($this->vc->udpHeartbeat);
            $this->vc->udpHeartbeat = null;
        }

        // Close UDP socket.
        if (isset($this->client)) {
            $this->bot->logger->warning('closing UDP client');
            $this->client->close();
        }

        // Don't reconnect on a critical opcode or if closed by user.
        if (in_array($op, Op::getCriticalVoiceCloseCodes()) || $this?->userClose) {
            $this->bot->logger->warning('received critical opcode - not reconnecting', ['op' => $op, 'reason' => $reason]);
            $this->vc->emit('close');

            return;
        }

        if (in_array($op, [Op::CLOSE_VOICE_DISCONNECTED])) {
            $this->vc->emit('close');

            return;
        }

        $this->bot->logger->warning('reconnecting in 2 seconds');

        // Retry connect after 2 seconds
        $this->bot->loop->addTimer(2, function (): void {
            $this->reconnecting = true;
            $this->sentLoginFrame = false;

            $this->vc->start();
        });
    }
}
