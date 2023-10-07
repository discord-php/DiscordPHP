<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord;

use Discord\Exceptions\IntentException;
use Discord\Factory\Factory;
use Discord\Helpers\BigInt;
use Discord\Helpers\CacheConfig;
use Discord\Http\Http;
use Discord\Parts\Guild\Guild;
use Discord\Parts\OAuth\Application;
use Discord\Parts\Part;
use Discord\Repository\AbstractRepository;
use Discord\Repository\GuildRepository;
use Discord\Repository\PrivateChannelRepository;
use Discord\Repository\UserRepository;
use Discord\Parts\Channel\Channel;
use Discord\Parts\User\Activity;
use Discord\Parts\User\Client;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Voice\VoiceClient;
use Discord\WebSockets\Event;
use Discord\WebSockets\Events\GuildCreate;
use Discord\WebSockets\Handlers;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Op;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monolog;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Message;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use Discord\Helpers\Deferred;
use Discord\Helpers\RegisteredCommand;
use Discord\Http\Drivers\React;
use Discord\Http\Endpoint;
use Evenement\EventEmitterTrait;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;
use React\Cache\ArrayCache;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\PromiseInterface;
use React\Socket\Connector as SocketConnector;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function React\Async\coroutine;
use function React\Promise\all;

/**
 * The Discord client class.
 *
 * @version 10.0.0
 *
 * @property string                   $id               The unique identifier of the client.
 * @property string                   $username         The username of the client.
 * @property string                   $password         The password of the client (if they have provided it).
 * @property string                   $email            The email of the client.
 * @property bool                     $verified         Whether the client has verified their email.
 * @property string                   $avatar           The avatar URL of the client.
 * @property string                   $avatar_hash      The avatar hash of the client.
 * @property string                   $discriminator    The unique discriminator of the client.
 * @property bool                     $bot              Whether the client is a bot.
 * @property User                     $user             The user instance of the client.
 * @property Application              $application      The OAuth2 application of the bot.
 * @property GuildRepository          $guilds
 * @property PrivateChannelRepository $private_channels
 * @property UserRepository           $users
 */
class Discord
{
    use EventEmitterTrait;

    /**
     * The gateway version the client uses.
     *
     * @var int Gateway version.
     */
    public const GATEWAY_VERSION = 10;

    /**
     * The client version.
     *
     * @var string Version.
     */
    public const VERSION = 'v10.0.0';

    /**
     * The logger.
     *
     * @var LoggerInterface Logger.
     */
    protected $logger;

    /**
     * An array of loggers for voice clients.
     *
     * @var ?LoggerInterface[] Loggers.
     */
    protected $voiceLoggers = [];

    /**
     * An array of options passed to the client.
     *
     * @var array Options.
     */
    protected $options;

    /**
     * The authentication token.
     *
     * @var string Token.
     */
    protected $token;

    /**
     * The ReactPHP event loop.
     *
     * @var LoopInterface Event loop.
     */
    protected $loop;

    /**
     * The WebSocket client factory.
     *
     * @var Connector Factory.
     */
    protected $wsFactory;

    /**
     * The WebSocket instance.
     *
     * @var WebSocket Instance.
     */
    protected $ws;

    /**
     * The event handlers.
     *
     * @var Handlers Handlers.
     */
    protected $handlers;

    /**
     * The packet sequence that the client is up to.
     *
     * @var int Sequence.
     */
    protected $seq;

    /**
     * Whether the client is currently reconnecting.
     *
     * @var bool Reconnecting.
     */
    protected $reconnecting = false;

    /**
     * Whether the client is connected to the gateway.
     *
     * @var bool Connected.
     */
    protected $connected = false;

    /**
     * Whether the client is closing.
     *
     * @var bool Closing.
     */
    protected $closing = false;

    /**
     * The session ID of the current session.
     *
     * @var string Session ID.
     */
    protected $sessionId;

    /**
     * An array of voice clients that are currently connected.
     *
     * @var array Voice Clients.
     */
    protected $voiceClients = [];

    /**
     * An array of large guilds that need to be requested for members.
     *
     * @var array Large guilds.
     */
    protected $largeGuilds = [];

    /**
     * An array of large guilds that have been requested for members.
     *
     * @var array Large guilds.
     */
    protected $largeSent = [];

    /**
     * An array of unparsed packets.
     *
     * @var array Unparsed packets.
     */
    protected $unparsedPackets = [];

    /**
     * How many times the client has reconnected.
     *
     * @var int Reconnect count.
     */
    protected $reconnectCount = 0;

    /**
     * The heartbeat interval.
     *
     * @var int Heartbeat interval.
     */
    protected $heartbeatInterval;

    /**
     * The timer that sends the heartbeat packet.
     *
     * @var TimerInterface Timer.
     */
    protected $heartbeatTimer;

    /**
     * The timer that resends the heartbeat packet if a HEARTBEAT_ACK packet is
     * not received in 5 seconds.
     *
     * @var TimerInterface Timer.
     */
    protected $heartbeatAckTimer;

    /**
     * The time that the last heartbeat packet was sent.
     *
     * @var int Epoch time.
     */
    protected $heartbeatTime;

    /**
     * Whether `init` has been emitted.
     *
     * @var bool Emitted.
     */
    protected $emittedInit = false;

    /**
     * The gateway URL that the WebSocket client will connect to.
     *
     * @var string Gateway URL.
     */
    protected $gateway;

    /**
     * The resume_gateway_url that the WebSocket client will reconnect to.
     *
     * @var string resume_gateway_url URL.
     */
    protected $resume_gateway_url;

    /**
     * What encoding the client will use, either `json` or `etf`.
     *
     * @var string Encoding.
     */
    protected $encoding = 'json';

    /**
     * Gateway compressed message payload buffer.
     *
     * @var string Buffer.
     */
    protected $payloadBuffer = '';

    /**
     * zlib decompressor.
     *
     * @var \InflateContext|false
     */
    protected $zlibDecompressor;

    /**
     * Tracks the number of payloads the client has sent in the past 60 seconds.
     *
     * @var int
     */
    protected $payloadCount = 0;

    /**
     * Payload count reset timer.
     *
     * @var TimerInterface
     */
    protected $payloadTimer;

    /**
     * The HTTP client.
     *
     * @var Http Client.
     */
    protected $http;

    /**
     * The part/repository factory.
     *
     * @var Factory Part factory.
     */
    protected $factory;

    /**
     * The cache configuration.
     *
     * @var CacheConfig
     */
    protected $cacheConfig;

    /**
     * The Client class.
     *
     * @var Client Discord client.
     */
    protected $client;

    /**
     * An array of registered slash commands.
     *
     * @var RegisteredCommand[]
     */
    private $application_commands;

    /**
     * Creates a Discord client instance.
     *
     * @param  array           $options Array of options.
     * @throws IntentException
     */
    public function __construct(array $options = [])
    {
        if (php_sapi_name() !== 'cli') {
            trigger_error('DiscordPHP will not run on a webserver. Please use PHP CLI to run a DiscordPHP bot.', E_USER_ERROR);
        }

        // x86 need gmp extension for big integer operation
        if (PHP_INT_SIZE === 4 && ! BigInt::init()) {
            trigger_error('ext-gmp is not loaded, it is required for 32-bits (x86) PHP.', E_USER_ERROR);
        }

        $options = $this->resolveOptions($options);

        $this->options = $options;
        $this->token = $options['token'];
        $this->loop = $options['loop'];
        $this->logger = $options['logger'];

        $this->logger->debug('Initializing DiscordPHP '.self::VERSION.' (DiscordPHP-Http: '.Http::VERSION.' & Gateway: v'.self::GATEWAY_VERSION.') on PHP '.PHP_VERSION);

        $this->cacheConfig = $options['cache'];
        $this->logger->warning('Attached experimental CacheInterface: '.get_class($this->getCacheConfig()->interface));

        $connector = new SocketConnector($options['socket_options'], $this->loop);
        $this->wsFactory = new Connector($this->loop, $connector);
        $this->handlers = new Handlers();

        foreach ($options['disabledEvents'] as $event) {
            $this->handlers->removeHandler($event);
        }

        $this->http = new Http(
            'Bot '.$this->token,
            $this->loop,
            $this->options['logger'],
            new React($this->loop, $options['socket_options'])
        );

        $this->factory = new Factory($this);
        $this->client = $this->factory->part(Client::class, []);

        $this->connectWs();
    }

    /**
     * Handles `VOICE_SERVER_UPDATE` packets.
     *
     * @param object $data Packet data.
     */
    protected function handleVoiceServerUpdate(object $data): void
    {
        if (isset($this->voiceClients[$data->d->guild_id])) {
            $this->logger->debug('voice server update received', ['guild' => $data->d->guild_id, 'data' => $data->d]);
            $this->voiceClients[$data->d->guild_id]->handleVoiceServerChange((array) $data->d);
        }
    }

    /**
     * Handles `RESUME` packets.
     *
     * @param object $data Packet data.
     */
    protected function handleResume(object $data): void
    {
        $this->logger->info('websocket reconnected to discord');
        $this->emit('reconnected', [$this]);
    }

    /**
     * Handles `READY` packets.
     *
     * @param object $data Packet data.
     *
     * @return false|void
     * @throws \Exception
     */
    protected function handleReady(object $data)
    {
        $this->logger->debug('ready packet received');

        $content = $data->d;

        // Check if we received resume_gateway_url
        if (isset($content->resume_gateway_url)) {
            $this->resume_gateway_url = $content->resume_gateway_url;
            $this->logger->debug('resume_gateway_url received', ['url' => $content->resume_gateway_url]);
        }

        // If this is a reconnect we don't want to
        // reparse the READY packet as it would remove
        // all the data cached.
        if ($this->reconnecting) {
            $this->reconnecting = false;
            $this->logger->debug('websocket reconnected to discord through identify');
            $this->emit('reconnected', [$this]);

            return;
        }

        $this->emit('trace', $data->d->_trace);
        $this->logger->debug('discord trace received', ['trace' => $content->_trace]);

        // Setup the user account
        $this->client->fill((array) $content->user);
        $this->client->created = true;
        $this->sessionId = $content->session_id;

        $this->logger->debug('client created and session id stored', ['session_id' => $content->session_id, 'user' => $this->client->user->getPublicAttributes()]);

        // Guilds
        $event = new GuildCreate($this);

        $unavailable = [];

        foreach ($content->guilds as $guild) {
            /** @var ExtendedPromiseInterface */
            $promise = coroutine([$event, 'handle'], $guild);

            $promise->done(function ($d) use (&$unavailable) {
                if (! empty($d->unavailable)) {
                    $unavailable[$d->id] = $d->unavailable;
                }
            });
        }

        $this->logger->info('stored guilds', ['count' => $this->guilds->count(), 'unavailable' => count($unavailable)]);

        if (count($unavailable) < 1) {
            return $this->ready();
        }

        // Emit ready after 60 seconds
        $this->loop->addTimer(60, function () {
            $this->ready();
        });

        $guildLoad = new Deferred();

        $onGuildCreate = function ($guild) use (&$unavailable, $guildLoad) {
            if (empty($guild->unavailable)) {
                $this->logger->debug('guild available', ['guild' => $guild->id, 'unavailable' => count($unavailable)]);
                unset($unavailable[$guild->id]);
            }
            if (count($unavailable) < 1) {
                $guildLoad->resolve();
            }
        };
        $this->on(Event::GUILD_CREATE, $onGuildCreate);

        $onGuildDelete = function ($guild) use (&$unavailable, $guildLoad) {
            if ($guild->unavailable) {
                $this->logger->debug('guild unavailable', ['guild' => $guild->id, 'unavailable' => count($unavailable)]);
                unset($unavailable[$guild->id]);
            }
            if (count($unavailable) < 1) {
                $guildLoad->resolve();
            }
        };
        $this->on(Event::GUILD_DELETE, $onGuildDelete);

        $guildLoad->promise()->always(function () use ($onGuildCreate, $onGuildDelete) {
            $this->removeListener(Event::GUILD_CREATE, $onGuildCreate);
            $this->removeListener(Event::GUILD_DELETE, $onGuildDelete);
            $this->logger->info('all guilds are now available', ['count' => $this->guilds->count()]);

            $this->setupChunking();
        })->done();
    }

    /**
     * Handles `GUILD_MEMBERS_CHUNK` packets.
     *
     * @param  object     $data Packet data.
     * @throws \Exception
     */
    protected function handleGuildMembersChunk(object $data): void
    {
        if (! $guild = $this->guilds->get('id', $data->d->guild_id)) {
            $this->logger->warning('not chunking member, Guild is not cached.', ['guild_id' => $data->d->guild_id]);

            return;
        }

        $this->logger->debug('received guild member chunk', ['guild_id' => $data->d->guild_id, 'guild_name' => $guild->name, 'chunk_count' => count($data->d->members), 'member_collection' => $guild->members->count(), 'member_count' => $guild->member_count, 'progress' => [$data->d->chunk_index + 1, $data->d->chunk_count]]);

        $count = $skipped = 0;
        $await = [];
        foreach ($data->d->members as $member) {
            $userId = $member->user->id;
            if ($guild->members->offsetExists($userId)) {
                continue;
            }

            $member = (array) $member;
            $member['guild_id'] = $data->d->guild_id;
            $member['status'] = 'offline';
            $await[] = $guild->members->cache->set($userId, $this->factory->part(Member::class, $member, true));

            if (! $this->users->offsetExists($userId)) {
                $await[] = $this->users->cache->set($userId, $this->users->create($member['user'], true));
            }

            ++$count;
        }

        all($await)->then(function () use ($guild, $count, $skipped) {
            $membersCount = $guild->members->count();
            $this->logger->debug('parsed '.$count.' members (skipped '.$skipped.')', ['repository_count' => $membersCount, 'actual_count' => $guild->member_count]);

            if ($membersCount >= $guild->member_count) {
                $this->largeSent = array_diff($this->largeSent, [$guild->id]);

                $this->logger->debug('all users have been loaded', ['guild' => $guild->id, 'member_collection' => $membersCount, 'member_count' => $guild->member_count]);
            }

            if (count($this->largeSent) < 1) {
                $this->ready();
            }
        });
    }

    /**
     * Handles `VOICE_STATE_UPDATE` packets.
     *
     * @param object $data Packet data.
     */
    protected function handleVoiceStateUpdate(object $data): void
    {
        if (isset($this->voiceClients[$data->d->guild_id])) {
            $this->logger->debug('voice state update received', ['guild' => $data->d->guild_id, 'data' => $data->d]);
            $this->voiceClients[$data->d->guild_id]->handleVoiceStateUpdate($data->d);
        }
    }

    /**
     * Handles WebSocket connections received by the client.
     *
     * @param WebSocket $ws WebSocket client.
     */
    public function handleWsConnection(WebSocket $ws): void
    {
        $this->ws = $ws;
        $this->connected = true;

        $this->logger->info('websocket connection has been created');

        $this->payloadCount = 0;
        $this->payloadTimer = $this->loop->addPeriodicTimer(60, function () {
            $this->logger->debug('resetting payload count', ['count' => $this->payloadCount]);
            $this->payloadCount = 0;
            $this->emit('payload_count_reset');
        });

        $ws->on('message', [$this, 'handleWsMessage']);
        $ws->on('close', [$this, 'handleWsClose']);
        $ws->on('error', [$this, 'handleWsError']);
    }

    /**
     * Handles WebSocket messages received by the client.
     *
     * @param Message $message Message object.
     */
    public function handleWsMessage(Message $message): void
    {
        $payload = $message->getPayload();

        if ($message->isBinary()) {
            if ($this->zlibDecompressor) {
                $this->payloadBuffer .= $payload;

                if ($message->getPayloadLength() < 4 || substr($payload, -4) != "\x00\x00\xff\xff") {
                    return;
                }

                $this->processWsMessage(inflate_add($this->zlibDecompressor, $this->payloadBuffer));
                $this->payloadBuffer = '';
            } else {
                $this->processWsMessage(zlib_decode($payload));
            }
        } else {
            $this->processWsMessage($payload);
        }
    }

    /**
     * Process WebSocket message payloads.
     *
     * @param string $data Message payload.
     */
    protected function processWsMessage(string $data): void
    {
        $data = json_decode($data);
        $this->emit('raw', [$data, $this]);

        if (isset($data->s)) {
            $this->seq = $data->s;
        }

        $op = [
            Op::OP_DISPATCH => 'handleDispatch',
            Op::OP_HEARTBEAT => 'handleHeartbeat',
            Op::OP_RECONNECT => 'handleReconnect',
            Op::OP_INVALID_SESSION => 'handleInvalidSession',
            Op::OP_HELLO => 'handleHello',
            Op::OP_HEARTBEAT_ACK => 'handleHeartbeatAck',
        ];

        if (isset($op[$data->op])) {
            $this->{$op[$data->op]}($data);
        }
    }

    /**
     * Handles WebSocket closes received by the client.
     *
     * @param int    $op     The close code.
     * @param string $reason The reason the WebSocket closed.
     */
    public function handleWsClose(int $op, string $reason): void
    {
        $this->connected = false;

        if (null !== $this->heartbeatTimer) {
            $this->loop->cancelTimer($this->heartbeatTimer);
            $this->heartbeatTimer = null;
        }

        if (null !== $this->heartbeatAckTimer) {
            $this->loop->cancelTimer($this->heartbeatAckTimer);
            $this->heartbeatAckTimer = null;
        }

        if (null !== $this->payloadTimer) {
            $this->loop->cancelTimer($this->payloadTimer);
            $this->payloadTimer = null;
        }

        if ($this->closing) {
            return;
        }

        $this->logger->warning('websocket closed', ['op' => $op, 'reason' => $reason]);

        if (in_array($op, Op::getCriticalCloseCodes())) {
            $this->logger->error('not reconnecting - critical op code', ['op' => $op, 'reason' => $reason]);
        } else {
            $this->logger->warning('reconnecting in 2 seconds');

            $this->loop->addTimer(2, function () {
                ++$this->reconnectCount;
                $this->reconnecting = true;
                $this->logger->info('starting reconnect', ['reconnect_count' => $this->reconnectCount]);
                $this->connectWs();
            });
        }
    }

    /**
     * Handles WebSocket errors received by the client.
     *
     * @param \Exception $e The error.
     */
    public function handleWsError(\Exception $e): void
    {
        // Pawl pls
        if (strpos($e->getMessage(), 'Tried to write to closed stream') !== false) {
            return;
        }

        $this->logger->error('websocket error', ['e' => $e->getMessage()]);
        $this->emit('error', [$e, $this]);
        $this->ws->close(Op::CLOSE_ABNORMAL, $e->getMessage());
    }

    /**
     * Handles cases when the WebSocket cannot be connected to.
     *
     * @param \Throwable $e
     */
    public function handleWsConnectionFailed(\Throwable $e)
    {
        $this->logger->error('failed to connect to websocket, retry in 5 seconds', ['e' => $e->getMessage()]);

        $this->loop->addTimer(5, function () {
            $this->connectWs();
        });
    }

    /**
     * Handles dispatch events received by the WebSocket.
     *
     * @param object $data Packet data.
     */
    protected function handleDispatch(object $data): void
    {
        $hData = $this->handlers->getHandler($data->t);

        if (null === $hData) {
            $handlers = [
                Event::VOICE_SERVER_UPDATE => 'handleVoiceServerUpdate',
                Event::RESUMED => 'handleResume',
                Event::READY => 'handleReady',
                Event::GUILD_MEMBERS_CHUNK => 'handleGuildMembersChunk',
                Event::VOICE_STATE_UPDATE => 'handleVoiceStateUpdate',
            ];

            if (isset($handlers[$data->t])) {
                $this->{$handlers[$data->t]}($data);
            }

            return;
        }

        /** @var Event */
        $handler = new $hData['class']($this);

        $deferred = new Deferred();
        $deferred->promise()->done(function ($d) use ($data, $hData) {
            if (is_array($d) && count($d) == 2) {
                list($new, $old) = $d;
            } else {
                $new = $d;
                $old = null;
            }

            $this->emit($data->t, [$new, $this, $old]);

            foreach ($hData['alternatives'] as $alternative) {
                $this->emit($alternative, [$d, $this]);
            }

            if ($data->t == Event::MESSAGE_CREATE && mentioned($this->client->user, $new)) {
                $this->emit('mention', [$new, $this, $old]);
            }
        }, function ($e) use ($data) {
            if ($e instanceof \Error) {
                throw $e;
            } elseif ($e instanceof \Exception) {
                $this->logger->error('exception while trying to handle dispatch packet', ['packet' => $data->t, 'exception' => $e]);
            } else {
                $this->logger->warning('rejection while trying to handle dispatch packet', ['packet' => $data->t, 'rejection' => $e]);
            }
        });

        $parse = [
            Event::GUILD_CREATE,
            Event::GUILD_DELETE,
        ];

        if (! $this->emittedInit && (! in_array($data->t, $parse))) {
            $this->unparsedPackets[] = function () use (&$handler, &$deferred, &$data) {
                /** @var ExtendedPromiseInterface */
                $promise = coroutine([$handler, 'handle'], $data->d);
                $promise->done([$deferred, 'resolve'], [$deferred, 'reject']);
            };
        } else {
            /** @var ExtendedPromiseInterface */
            $promise = coroutine([$handler, 'handle'], $data->d);
            $promise->done([$deferred, 'resolve'], [$deferred, 'reject']);
        }
    }

    /**
     * Handles heartbeat packets received by the client.
     *
     * @param object $data Packet data.
     */
    protected function handleHeartbeat(object $data): void
    {
        $this->logger->debug('received heartbeat', ['seq' => $data->d]);

        $payload = [
            'op' => Op::OP_HEARTBEAT,
            'd' => $data->d,
        ];

        $this->send($payload);
    }

    /**
     * Handles heartbeat ACK packets received by the client.
     *
     * @param object $data Packet data.
     */
    protected function handleHeartbeatAck(object $data): void
    {
        $received = microtime(true);
        $diff = $received - $this->heartbeatTime;
        $time = $diff * 1000;

        if (null !== $this->heartbeatAckTimer) {
            $this->loop->cancelTimer($this->heartbeatAckTimer);
            $this->heartbeatAckTimer = null;
        }

        $this->emit('heartbeat-ack', [$time, $this]);
        $this->logger->debug('received heartbeat ack', ['response_time' => $time]);
    }

    /**
     * Handles reconnect packets received by the client.
     *
     * @param object $data Packet data.
     */
    protected function handleReconnect(object $data): void
    {
        $this->logger->warning('received opcode 7 for reconnect');

        $this->ws->close(
            Op::CLOSE_UNKNOWN_ERROR,
            'gateway redirecting - opcode 7'
        );
    }

    /**
     * Handles invalid session packets received by the client.
     *
     * @param object $data Packet data.
     */
    protected function handleInvalidSession(object $data): void
    {
        $this->logger->warning('invalid session, re-identifying', ['resumable' => $data->d]);

        $this->loop->addTimer(2, function () use ($data) {
            $this->identify($data->d);
        });
    }

    /**
     * Handles HELLO packets received by the websocket.
     *
     * @param object $data Packet data.
     */
    protected function handleHello(object $data): void
    {
        $this->logger->info('received hello');
        $this->setupHeartbeat($data->d->heartbeat_interval);
        $this->identify();
    }

    /**
     * Identifies with the Discord gateway with `IDENTIFY` or `RESUME` packets.
     *
     * @param  bool $resume Whether resume should be enabled.
     * @return bool
     */
    protected function identify(bool $resume = true): bool
    {
        if ($resume && $this->reconnecting && null !== $this->sessionId) {
            $payload = [
                'op' => Op::OP_RESUME,
                'd' => [
                    'session_id' => $this->sessionId,
                    'seq' => $this->seq,
                    'token' => $this->token,
                ],
            ];

            $reason = 'resuming connection';
        } else {
            $payload = [
                'op' => Op::OP_IDENTIFY,
                'd' => [
                    'token' => $this->token,
                    'properties' => [
                        'os' => PHP_OS,
                        'browser' => $this->http->getUserAgent(),
                        'device' => $this->http->getUserAgent(),
                        'referrer' => 'https://github.com/discord-php/DiscordPHP',
                        'referring_domain' => 'https://github.com/discord-php/DiscordPHP',
                    ],
                    'compress' => true,
                    'intents' => $this->options['intents'],
                ],
            ];

            if (
                array_key_exists('shardId', $this->options) &&
                array_key_exists('shardCount', $this->options)
            ) {
                $payload['d']['shard'] = [
                    (int) $this->options['shardId'],
                    (int) $this->options['shardCount'],
                ];
            }

            $reason = 'identifying';
        }

        $safePayload = $payload;
        $safePayload['d']['token'] = 'xxxxxx';

        $this->logger->info($reason, ['payload' => $safePayload]);

        $this->send($payload);

        return $payload['op'] == Op::OP_RESUME;
    }

    /**
     * Sends a heartbeat packet to the Discord gateway.
     */
    public function heartbeat(): void
    {
        $this->logger->debug('sending heartbeat', ['seq' => $this->seq]);

        $payload = [
            'op' => Op::OP_HEARTBEAT,
            'd' => $this->seq,
        ];

        $this->send($payload, true);
        $this->heartbeatTime = microtime(true);
        $this->emit('heartbeat', [$this->seq, $this]);

        $this->heartbeatAckTimer = $this->loop->addTimer($this->heartbeatInterval / 1000, function () {
            if (! $this->connected) {
                return;
            }

            $this->logger->warning('did not receive heartbeat ACK within heartbeat interval, closing connection');
            $this->ws->close(1001, 'did not receive heartbeat ack');
        });
    }

    /**
     * Sets guild member chunking up.
     *
     * @return false|void
     */
    protected function setupChunking()
    {
        if ($this->options['loadAllMembers'] === false) {
            $this->logger->info('loadAllMembers option is disabled, not setting chunking up');

            return $this->ready();
        }

        $checkForChunks = function () {
            if ((count($this->largeGuilds) < 1) && (count($this->largeSent) < 1)) {
                $this->ready();

                return;
            }

            if (count($this->largeGuilds) < 1) {
                $this->logger->debug('unprocessed chunks', $this->largeSent);

                return;
            }

            if (is_array($this->options['loadAllMembers'])) {
                foreach ($this->largeGuilds as $key => $guild) {
                    if (! in_array($guild, $this->options['loadAllMembers'])) {
                        $this->logger->debug('not fetching members for guild ID '.$guild);
                        unset($this->largeGuilds[$key]);
                    }
                }
            }

            $chunks = array_chunk($this->largeGuilds, 50);
            $this->logger->debug('sending '.count($chunks).' chunks with '.count($this->largeGuilds).' large guilds overall');
            $this->largeSent = array_merge($this->largeGuilds, $this->largeSent);
            $this->largeGuilds = [];

            $sendChunks = function () use (&$sendChunks, &$chunks) {
                $chunk = array_pop($chunks);

                if (null === $chunk) {
                    return;
                }

                $this->logger->debug('sending chunk with '.count($chunk).' large guilds');

                foreach ($chunk as $guild_id) {
                    $payload = [
                        'op' => Op::OP_GUILD_MEMBER_CHUNK,
                        'd' => [
                            'guild_id' => $guild_id,
                            'query' => '',
                            'limit' => 0,
                        ],
                    ];

                    $this->send($payload);
                }
                $this->loop->addTimer(1, $sendChunks);
            };

            $sendChunks();
        };

        $this->loop->addPeriodicTimer(5, $checkForChunks);
        $this->logger->info('set up chunking, checking for chunks every 5 seconds');
        $checkForChunks();
    }

    /**
     * Sets the heartbeat timer up.
     *
     * @param int $interval The heartbeat interval in milliseconds.
     */
    protected function setupHeartbeat(int $interval): void
    {
        $this->heartbeatInterval = $interval;
        if (isset($this->heartbeatTimer)) {
            $this->loop->cancelTimer($this->heartbeatTimer);
        }

        $interval = $interval / 1000;
        $this->heartbeatTimer = $this->loop->addPeriodicTimer($interval, [$this, 'heartbeat']);
        $this->heartbeat();

        $this->logger->info('heartbeat timer initilized', ['interval' => $interval * 1000]);
    }

    /**
     * Initializes the connection with the Discord gateway.
     */
    protected function connectWs(): void
    {
        $this->setGateway()->done(function ($gateway) {
            if (isset($gateway['session']) && $session = $gateway['session']) {
                if ($session['remaining'] < 2) {
                    $this->logger->error('exceeded number of reconnects allowed, waiting before attempting reconnect', $session);
                    $this->loop->addTimer($session['reset_after'] / 1000, function () {
                        $this->connectWs();
                    });

                    return;
                }
            }

            $this->logger->info('starting connection to websocket', ['gateway' => $this->gateway]);

            /** @var ExtendedPromiseInterface */
            $promise = ($this->wsFactory)($this->gateway);
            $promise->done(
                [$this, 'handleWsConnection'],
                [$this, 'handleWsConnectionFailed']
            );
        });
    }

    /**
     * Sends a packet to the Discord gateway.
     *
     * @param array $data Packet data.
     */
    protected function send(array $data, bool $force = false): void
    {
        // Wait until payload count has been reset
        // Keep 5 payloads for heartbeats as required
        if ($this->payloadCount >= 115 && ! $force) {
            $this->logger->debug('payload not sent, waiting', ['payload' => $data]);
            $this->once('payload_count_reset', function () use ($data) {
                $this->send($data);
            });
        } else {
            ++$this->payloadCount;
            $data = json_encode($data);
            $this->ws->send($data);
        }
    }

    /**
     * Emits init if it has not been emitted already.
     * @return false|void
     */
    protected function ready()
    {
        if ($this->emittedInit) {
            return false;
        }
        $this->emittedInit = true;

        $this->logger->info('client is ready');
        $this->emit('init', [$this]);

        if (count($this->listeners('ready'))) {
            $this->logger->info("The 'ready' event is deprecated and will be removed in a future version of DiscordPHP. Please use 'init' instead.");
            $this->emit('ready', [$this]); // deprecated
        }

        foreach ($this->unparsedPackets as $parser) {
            $parser();
        }
    }

    /**
     * Updates the clients presence.
     *
     * @param Activity|null $activity The current client activity, or null.
     *                                Note: The activity type _cannot_ be custom, and the only valid fields are `name`, `type` and `url`.
     * @param bool          $idle     Whether the client is idle.
     * @param string        $status   The current status of the client.
     *                                Must be one of the following:
     *                                online, dnd, idle, invisible, offline
     * @param bool          $afk      Whether the client is AFK.
     *
     * @throws \UnexpectedValueException
     */
    public function updatePresence(Activity $activity = null, bool $idle = false, string $status = 'online', bool $afk = false): void
    {
        $idle = $idle ? time() * 1000 : null;

        if (null !== $activity) {
            $activity = $activity->getRawAttributes();

            if (! in_array($activity['type'], [Activity::TYPE_GAME, Activity::TYPE_STREAMING, Activity::TYPE_LISTENING, Activity::TYPE_WATCHING, Activity::TYPE_CUSTOM, Activity::TYPE_COMPETING])) {
                throw new \UnexpectedValueException("The given activity type ({$activity['type']}) is invalid.");
            }
        }

        $allowed = ['online', 'dnd', 'idle', 'invisible', 'offline'];

        if (! in_array($status, $allowed)) {
            $status = 'online';
        }

        $payload = [
            'op' => Op::OP_PRESENCE_UPDATE,
            'd' => [
                'since' => $idle,
                'activities' => [$activity],
                'status' => $status,
                'afk' => $afk,
            ],
        ];

        $this->send($payload);
    }

    /**
     * Gets a voice client from a guild ID. Returns null if there is no voice client.
     *
     * @param string $guild_id The guild ID to look up.
     *
     * @return VoiceClient|null
     */
    public function getVoiceClient(string $guild_id): ?VoiceClient
    {
        return $this->voiceClients[$guild_id] ?? null;
    }

    /**
     * Joins a voice channel.
     *
     * @param Channel              $channel The channel to join.
     * @param bool                 $mute    Whether you should be mute when you join the channel.
     * @param bool                 $deaf    Whether you should be deaf when you join the channel.
     * @param LoggerInterface|null $logger  Voice client logger. If null, uses same logger as Discord client.
     *
     * @throws \RuntimeException
     *
     * @since 10.0.0 Removed argument $check that has no effect (it is always checked)
     * @since 4.0.0
     *
     * @return PromiseInterface
     */
    public function joinVoiceChannel(Channel $channel, $mute = false, $deaf = true, ?LoggerInterface $logger = null): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        if (! $channel->isVoiceBased()) {
            $deferred->reject(new \RuntimeException('Channel must allow voice.'));

            return $deferred->promise();
        }

        if (isset($this->voiceClients[$channel->guild_id])) {
            $deferred->reject(new \RuntimeException('You cannot join more than one voice channel per guild.'));

            return $deferred->promise();
        }

        $data = [
            'user_id' => $this->id,
            'deaf' => $deaf,
            'mute' => $mute,
        ];

        $voiceStateUpdate = function ($vs, $discord) use ($channel, &$data, &$voiceStateUpdate) {
            if ($vs->guild_id != $channel->guild_id) {
                return; // This voice state update isn't for our guild.
            }

            $data['session'] = $vs->session_id;
            $this->logger->info('received session id for voice session', ['guild' => $channel->guild_id, 'session_id' => $vs->session_id]);
            $this->removeListener(Event::VOICE_STATE_UPDATE, $voiceStateUpdate);
        };

        $voiceServerUpdate = function ($vs, $discord) use ($channel, &$data, &$voiceServerUpdate, $deferred, $logger) {
            if ($vs->guild_id != $channel->guild_id) {
                return; // This voice server update isn't for our guild.
            }

            $data['token'] = $vs->token;
            $data['endpoint'] = $vs->endpoint;
            $data['dnsConfig'] = $discord->options['dnsConfig'];
            $this->logger->info('received token and endpoint for voice session', ['guild' => $channel->guild_id, 'token' => $vs->token, 'endpoint' => $vs->endpoint]);

            if (null === $logger) {
                $logger = $this->logger;
            }

            $vc = new VoiceClient($this->ws, $this->loop, $channel, $logger, $data);

            $vc->once('ready', function () use ($vc, $deferred, $channel, $logger) {
                $logger->info('voice client is ready');
                $this->voiceClients[$channel->guild_id] = $vc;

                $vc->setBitrate($channel->bitrate);
                $logger->info('set voice client bitrate', ['bitrate' => $channel->bitrate]);
                $deferred->resolve($vc);
            });
            $vc->once('error', function ($e) use ($deferred, $logger) {
                $logger->error('error initilizing voice client', ['e' => $e->getMessage()]);
                $deferred->reject($e);
            });
            $vc->once('close', function () use ($channel, $logger) {
                $logger->warning('voice client closed');
                unset($this->voiceClients[$channel->guild_id]);
            });

            $vc->start();

            $this->voiceLoggers[$channel->guild_id] = $logger;
            $this->removeListener(Event::VOICE_SERVER_UPDATE, $voiceServerUpdate);
        };

        $this->on(Event::VOICE_STATE_UPDATE, $voiceStateUpdate);
        $this->on(Event::VOICE_SERVER_UPDATE, $voiceServerUpdate);

        $payload = [
            'op' => Op::OP_VOICE_STATE_UPDATE,
            'd' => [
                'guild_id' => $channel->guild_id,
                'channel_id' => $channel->id,
                'self_mute' => $mute,
                'self_deaf' => $deaf,
            ],
        ];

        $this->send($payload);

        return $deferred->promise();
    }

    /**
     * Retrieves and sets the gateway URL for the client.
     *
     * @param string|null $gateway Gateway URL to set.
     *
     * @return ExtendedPromiseInterface
     */
    protected function setGateway(?string $gateway = null): ExtendedPromiseInterface
    {
        $deferred = new Deferred();
        $defaultSession = [
            'total' => 1000,
            'remaining' => 1000,
            'reset_after' => 0,
            'max_concurrency' => 1,
        ];

        $buildParams = function ($gateway, $session = null) use ($deferred, $defaultSession) {
            $session = $session ?? $defaultSession;
            $params = [
                'v' => self::GATEWAY_VERSION,
                'encoding' => $this->encoding,
            ];

            if ($this->zlibDecompressor = inflate_init(ZLIB_ENCODING_DEFLATE)) {
                $params['compress'] = 'zlib-stream';
            }

            $query = http_build_query($params);
            $this->gateway = trim($gateway, '/').'/?'.$query;

            $deferred->resolve(['gateway' => $this->gateway, 'session' => (array) $session]);
        };

        if (null === $gateway) {
            $this->http->get(Endpoint::GATEWAY_BOT)->done(function ($response) use ($buildParams) {
                if ($response->shards > 1) {
                    $this->logger->info('Please contact the DiscordPHP devs at https://discord.gg/dphp or https://github.com/discord-php/DiscordPHP/issues if you are interrested in assisting us with sharding support development.');
                }
                $buildParams($this->resume_gateway_url ?? $response->url, $response->session_start_limit);
            }, function ($e) use ($buildParams) {
                // Can't access the API server so we will use the default gateway.
                $this->logger->warning('could not retrieve gateway, using default');
                $buildParams('wss://gateway.discord.gg');
            });
        } else {
            $buildParams($gateway);
        }

        $deferred->promise()->then(function ($gateway) {
            $this->logger->info('gateway retrieved and set', $gateway);
        }, function ($e) {
            $this->logger->error('error obtaining gateway', ['e' => $e->getMessage()]);
        });

        return $deferred->promise();
    }

    /**
     * Resolves the options.
     *
     * @param array $options Array of options.
     *
     * @return array           Options.
     * @throws IntentException
     */
    protected function resolveOptions(array $options = []): array
    {
        $resolver = new OptionsResolver();

        $resolver
            ->setRequired('token')
            ->setDefined([
                'token',
                'shardId',
                'shardCount',
                'loop',
                'logger',
                'loadAllMembers',
                'disabledEvents',
                'storeMessages',
                'retrieveBans',
                'intents',
                'socket_options',
                'dnsConfig',
                'cache',
            ])
            ->setDefaults([
                'logger' => null,
                'loadAllMembers' => false,
                'disabledEvents' => [],
                'storeMessages' => false,
                'retrieveBans' => false,
                'intents' => Intents::getDefaultIntents(),
                'socket_options' => [],
                'cache' => new ArrayCache(),
            ])
            ->setAllowedTypes('token', 'string')
            ->setAllowedTypes('logger', ['null', LoggerInterface::class])
            ->setAllowedTypes('loop', LoopInterface::class)
            ->setAllowedTypes('loadAllMembers', ['bool', 'array'])
            ->setAllowedTypes('disabledEvents', 'array')
            ->setAllowedTypes('storeMessages', 'bool')
            ->setAllowedTypes('retrieveBans', ['bool', 'array'])
            ->setAllowedTypes('intents', ['array', 'int'])
            ->setAllowedTypes('socket_options', 'array')
            ->setAllowedTypes('dnsConfig', ['string', \React\Dns\Config\Config::class])
            ->setAllowedTypes('cache', ['array', CacheConfig::class, \React\Cache\CacheInterface::class, \Psr\SimpleCache\CacheInterface::class])
            ->setNormalizer('cache', function ($options, $value) {
                if (! is_array($value)) {
                    if (! ($value instanceof CacheConfig)) {
                        $value = new CacheConfig($value);
                    }

                    return [AbstractRepository::class => $value];
                }

                return $value;
            });

        $options = $resolver->resolve($options);

        $options['loop'] ??= LoopFactory::create();

        if (null === $options['logger']) {
            $streamHandler = new StreamHandler('php://stdout', Monolog::DEBUG);
            $lineFormatter = new LineFormatter(null, null, true, true);
            $streamHandler->setFormatter($lineFormatter);
            $logger = new Monolog('DiscordPHP', [$streamHandler]);
            $options['logger'] = $logger;
        }

        if (! isset($options['dnsConfig'])) {
            $dnsConfig = \React\Dns\Config\Config::loadSystemConfigBlocking();
            if (! $dnsConfig->nameservers) {
                $dnsConfig->nameservers[] = '8.8.8.8';
            }

            $options['dnsConfig'] = $dnsConfig;
        }

        if (is_array($options['intents'])) {
            $intent = 0;
            $validIntents = Intents::getValidIntents();

            foreach ($options['intents'] as $idx => $i) {
                if (! in_array($i, $validIntents)) {
                    throw new IntentException('Given intent at index '.$idx.' is invalid.');
                }

                $intent |= $i;
            }

            $options['intents'] = $intent;
        }

        if ($options['loadAllMembers'] && ! ($options['intents'] & Intents::GUILD_MEMBERS)) {
            throw new IntentException('You have enabled the `loadAllMembers` option but have not enabled the required `GUILD_MEMBERS` intent.'.
            'See the documentation on the `loadAllMembers` property for more information: http://discord-php.github.io/DiscordPHP/#basics');
        }

        // Discord doesn't currently support IPv6
        // This prevents xdebug from catching exceptions when trying to fetch IPv6
        // for Discord
        $options['socket_options']['happy_eyeballs'] = false;

        return $options;
    }

    /**
     * Adds a large guild to the large guild array.
     *
     * @param Guild $guild The guild.
     */
    public function addLargeGuild(Part $guild): void
    {
        $this->largeGuilds[] = $guild->id;
    }

    /**
     * Starts the ReactPHP event loop.
     */
    public function run(): void
    {
        $this->loop->run();
    }

    /**
     * Closes the Discord client.
     *
     * @param bool $closeLoop Whether to close the loop as well. Default true.
     */
    public function close(bool $closeLoop = true): void
    {
        $this->closing = true;
        if ($this->ws) {
            $this->ws->close($closeLoop ? Op::CLOSE_UNKNOWN_ERROR : Op::CLOSE_NORMAL, 'discordphp closing...');
        }
        $this->emit('closed', [$this]);
        $this->logger->info('discord closed');

        if ($closeLoop) {
            $this->loop->stop();
        }
    }

    /**
     * Allows access to the part/repository factory.
     *
     * @param string $class   The class to build.
     * @param mixed  $data    Data to create the object.
     * @param bool   $created Whether the object is created (if part).
     *
     * @return Part|AbstractRepository
     *
     * @see Factory::create()
     *
     * @deprecated 10.0.0 Use `new $class($discord, ...)`.
     */
    public function factory(string $class, $data = [], bool $created = false)
    {
        return $this->factory->create($class, $data, $created);
    }

    /**
     * Gets the factory.
     *
     * @return Factory
     */
    public function getFactory(): Factory
    {
        return $this->factory;
    }

    /**
     * Gets the HTTP client.
     *
     * @return Http
     */
    public function getHttpClient(): Http
    {
        return $this->http;
    }

    /**
     * Gets the loop being used by the client.
     *
     * @return LoopInterface
     */
    public function getLoop(): LoopInterface
    {
        return $this->loop;
    }

    /**
     * Gets the logger being used.
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Gets the HTTP client.
     *
     * @return Http
     *
     * @deprecated Use Discord::getHttpClient()
     */
    public function getHttp(): Http
    {
        return $this->http;
    }

    /**
     * Gets the cache configuration.
     *
     * @param string $name Repository class name.
     *
     * @return CacheConfig
     */
    public function getCacheConfig($repository_class = AbstractRepository::class)
    {
        if (! isset($this->cacheConfig[$repository_class])) {
            $repository_class = AbstractRepository::class;
        }

        return $this->cacheConfig[$repository_class];
    }

    /**
     * Handles dynamic get calls to the client.
     *
     * @param string $name Variable name.
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        $allowed = ['loop', 'options', 'logger', 'http', 'application_commands'];

        if (in_array($name, $allowed)) {
            return $this->{$name};
        }

        if (null === $this->client) {
            return;
        }

        return $this->client->{$name};
    }

    /**
     * Handles dynamic set calls to the client.
     *
     * @param string $name  Variable name.
     * @param mixed  $value Value to set.
     */
    public function __set(string $name, $value): void
    {
        if (null === $this->client) {
            return;
        }

        $this->client->{$name} = $value;
    }

    /**
     * Gets a channel.
     *
     * @param string|int $channel_id Id of the channel.
     *
     * @return Channel|null
     */
    public function getChannel($channel_id): ?Channel
    {
        foreach ($this->guilds as $guild) {
            if ($channel = $guild->channels->get('id', $channel_id)) {
                return $channel;
            }
        }

        if ($channel = $this->private_channels->get('id', $channel_id)) {
            return $channel;
        }

        return null;
    }

    /**
     * Add listerner for incoming application command from interaction.
     *
     * @param string|array  $name
     * @param callable      $callback
     * @param callable|null $autocomplete_callback
     *
     * @throws \LogicException
     *
     * @return RegisteredCommand
     */
    public function listenCommand($name, callable $callback = null, ?callable $autocomplete_callback = null): RegisteredCommand
    {
        if (is_array($name) && count($name) == 1) {
            $name = array_shift($name);
        }

        // registering base command
        if (! is_array($name) || count($name) == 1) {
            if (isset($this->application_commands[$name])) {
                throw new \LogicException("The command `{$name}` already exists.");
            }

            return $this->application_commands[$name] = new RegisteredCommand($this, $name, $callback, $autocomplete_callback);
        }

        $baseCommand = array_shift($name);

        if (! isset($this->application_commands[$baseCommand])) {
            $this->listenCommand($baseCommand);
        }

        return $this->application_commands[$baseCommand]->addSubCommand($name, $callback, $autocomplete_callback);
    }

    /**
     * Handles dynamic calls to the client.
     *
     * @param string $name   Function name.
     * @param array  $params Function paramaters.
     *
     * @return mixed
     */
    public function __call(string $name, array $params)
    {
        if (null === $this->client) {
            return;
        }

        return call_user_func_array([$this->client, $name], $params);
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        $secrets = [
            'token' => '*****',
        ];
        $replace = array_intersect_key($secrets, $this->options);
        $config = $replace + $this->options;

        unset($config['loop'], $config['logger']);

        $config[] = $this->client;

        return $config;
    }
}
