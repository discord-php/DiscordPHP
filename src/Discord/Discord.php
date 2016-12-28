<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Discord\Factory\Factory;
use Discord\Http\Guzzle;
use Discord\Http\Http;
use Discord\Wrapper\LoggerWrapper as Logger;
use Discord\Parts\Channel\Channel;
use Discord\Parts\User\Client;
use Discord\Parts\User\Game;
use Discord\Parts\User\Member;
use Discord\Repository\GuildRepository;
use Discord\Repository\PrivateChannelRepository;
use Discord\Voice\VoiceClient;
use Discord\WebSockets\Event;
use Discord\WebSockets\Events\GuildCreate;
use Discord\WebSockets\Handlers;
use Discord\WebSockets\Op;
use Discord\Wrapper\CacheWrapper;
use Evenement\EventEmitterTrait;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monolog;
use Psr\Cache\CacheItemPoolInterface;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Message;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Promise\Deferred;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The Discord client class.
 */
class Discord
{
    use EventEmitterTrait;

    /**
     * The gateway version the client uses.
     *
     * @var int Gateway version.
     */
    const GATEWAY_VERSION = 6;

    /**
     * The HTTP API version the client usees.
     *
     * @var int HTTP API version.
     */
    const HTTP_API_VERSION = 6;

    /**
     * The client version.
     *
     * @var string Version.
     */
    const VERSION = 'v4.0.3';

    /**
     * The logger.
     *
     * @var Logger Logger.
     */
    protected $logger;

    /**
     * An array of loggers for voice clients.
     *
     * @var array Loggers.
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
     * @var WsFactory Factory.
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
     * An array of large guilds that need to be requested for
     * members.
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
    protected $heatbeatTimer;

    /**
     * The timer that resends the heartbeat packet if
     * a HEARTBEAT_ACK packet is not received in 5 seconds.
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
     * Whether `ready` has been emitted.
     *
     * @var bool Emitted.
     */
    protected $emittedReady = false;

    /**
     * The gateway URL that the WebSocket client will connect to.
     *
     * @var string Gateway URL.
     */
    protected $gateway;

    /**
     * What encoding the client will use, either `json` or `etf`.
     *
     * @var string Encoding.
     */
    protected $encoding = 'json';

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
     * The cache wrapper.
     *
     * @var CacheWrapper Cache.
     */
    protected $cache;

    /**
     * The cache pool that is in use.
     *
     * @var CacheItemPoolInterface Cache pool.
     */
    protected $cachePool;

    /**
     * The Client class.
     *
     * @var Client Discord client.
     */
    protected $client;

    /**
     * Creates a Discord client instance.
     *
     * @param array $options Array of options.
     */
    public function __construct(array $options = [])
    {
        $options = $this->resolveOptions($options);

        $this->token     = $options['token'];
        $this->loop      = $options['loop'];
        $this->logger    = new Logger($options['logger'], $options['logging']);
        $this->wsFactory = new Connector($this->loop);
        $this->handlers  = new Handlers();
        $this->cachePool = $options['cachePool'];

        $this->on('ready', function () {
            $this->emittedReady = true;
        });

        foreach ($options['disabledEvents'] as $event) {
            $this->handlers->removeHandler($event);
        }

        $this->options = $options;

        $this->cache = new CacheWrapper($this->cachePool); // todo cache pool
        $this->http  = new Http(
            $this->cache,
            ($options['bot'] ? 'Bot ' : '').$this->token,
            self::VERSION,
            new Guzzle($this->cache, $this->loop)
        );
        $this->factory = new Factory($this, $this->http, $this->cache);

        $this->setGateway()->then(function ($g) {
            $this->connectWs();
        });

        if (php_sapi_name() !== 'cli') {
            trigger_error('DiscordPHP will not run on a webserver. Please use PHP CLI to run a DiscordPHP bot.', E_USER_ERROR);
        }
    }

    /**
     * Handles `VOICE_SERVER_UPDATE` packets.
     *
     * @param object $data Packet data.
     */
    protected function handleVoiceServerUpdate($data)
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
    protected function handleResume($data)
    {
        $this->logger->info('websocket reconnected to discord');
        $this->emit('reconnected', [$this]);
    }

    /**
     * Handles `READY` packets.
     *
     * @param object $data Packet data.
     */
    protected function handleReady($data)
    {
        $this->logger->debug('ready packet received');

        // If this is a reconnect we don't want to
        // reparse the READY packet as it would remove
        // all the data cached.
        if ($this->reconnecting) {
            $this->reconnecting = false;
            $this->logger->debug('websocket reconnected to discord through identify');

            return;
        }

        $content = $data->d;
        $this->emit('trace', $data->d->_trace);
        $this->logger->debug('discord trace received', ['trace' => $content->_trace]);

        // Setup the user account
        $this->client    = $this->factory->create(Client::class, $content->user, true);
        $this->sessionId = $content->session_id;

        $this->logger->debug('client created and session id stored', ['session_id' => $content->session_id, 'user' => $this->client->user->getPublicAttributes()]);

        // Private Channels
        $private_channels = new PrivateChannelRepository(
            $this->http,
            $this->cache,
            $this->factory
        );

        if ($this->options['pmChannels']) {
            foreach ($content->private_channels as $channel) {
                $channelPart = $this->factory->create(Channel::class, $channel, true);
                $this->cache->set("pm_channels.{$channelPart->recipient->id}", $channelPart);
                $private_channels->push($channelPart);
            }

            $this->logger->info('stored private channels', ['count' => $private_channels->count()]);
        } else {
            $this->logger->info('did not parse private channels');
        }

        $this->private_channels = $private_channels;

        // Guilds
        $this->guilds = new GuildRepository(
            $this->http,
            $this->cache,
            $this->factory
        );
        $event = new GuildCreate(
            $this->http,
            $this->factory,
            $this->cache,
            $this
        );

        $unavailable = [];

        foreach ($content->guilds as $guild) {
            $deferred = new Deferred();

            $deferred->promise()->then(null, function ($d) use (&$unavailable) {
                list($status, $data) = $d;

                if ($status == 'unavailable') {
                    $unavailable[$data] = $data;
                }
            });

            $event->handle($deferred, $guild);
        }

        $this->logger->info('stored guilds', ['count' => $this->guilds->count()]);

        if (count($unavailable) < 1) {
            return $this->ready();
        }

        // Emit ready after 60 seconds
        $this->loop->addTimer(60, function () {
            $this->ready();
        });

        $function = function ($guild) use (&$function, &$unavailable) {
            if (array_key_exists($guild->id, $unavailable)) {
                unset($unavailable[$guild->id]);
            }

            // todo setup timer to continue after x amount of time
            if (count($unavailable) < 1) {
                $this->logger->info('all guilds are now available', ['count' => $this->guilds->count()]);
                $this->removeListener(Event::GUILD_CREATE, $function);

                $this->setupChunking();
            }
        };

        $this->on(Event::GUILD_CREATE, $function);
    }

    /**
     * Handles `GUILD_MEMBERS_CHUNK` packets.
     *
     * @param object $data Packet data.
     */
    protected function handleGuildMembersChunk($data)
    {
        $guild   = $this->guilds->get('id', $data->d->guild_id);
        $members = $data->d->members;

        $this->logger->debug('received guild member chunk', ['guild_id' => $guild->id, 'guild_name' => $guild->name, 'member_count' => count($members)]);

        $count = 0;

        foreach ($members as $member) {
            if (array_key_exists($member->user->id, $guild->members)) {
                continue;
            }

            $member             = (array) $member;
            $member['guild_id'] = $guild->id;
            $member['status']   = 'offline';
            $member['game']     = null;

            $memberPart = $this->factory->create(Member::class, $member, true);
            $guild->members->push($memberPart);
            $this->users->push($memberPart->user);
            ++$count;
        }

        $this->logger->debug('parsed '.$count.' members', ['repository_count' => $guild->members->count(), 'actual_count' => $guild->member_count]);

        if ($guild->members->count() >= $guild->member_count) {
            if (($key = array_search($guild->id, $this->largeSent)) !== false) {
                unset($this->largeSent[$key]);
            }

            $this->logger->debug('all users have been loaded', ['guild' => $guild->id, 'member_collection' => $guild->members->count(), 'member_count' => $guild->member_count]);
        }

        if (count($this->largeSent) < 1) {
            $this->ready();
        }
    }

    /**
     * Handles `VOICE_STATE_UPDATE` packets.
     *
     * @param object $data Packet data.
     */
    protected function handleVoiceStateUpdate($data)
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
    public function handleWsConnection(WebSocket $ws)
    {
        $this->ws        = $ws;
        $this->connected = true;

        $this->logger->info('websocket connection has been created');

        $ws->on('message', [$this, 'handleWsMessage']);
        $ws->on('close', [$this, 'handleWsClose']);
        $ws->on('error', [$this, 'handleWsError']);
    }

    /**
     * Handles WebSocket messages received by the client.
     *
     * @param Message $message Message object.
     */
    public function handleWsMessage($message)
    {
        if ($message->isBinary()) {
            $data = zlib_decode($message->getPayload());
        } else {
            $data = $message->getPayload();
        }

        $data = json_decode($data);
        $this->emit('raw', [$data, $this]);

        if (isset($data->s)) {
            $this->seq = $data->s;
        }

        $op = [
            Op::OP_DISPATCH        => 'handleDispatch',
            Op::OP_HEARTBEAT       => 'handleHeartbeat',
            Op::OP_RECONNECT       => 'handleReconnect',
            Op::OP_INVALID_SESSION => 'handleInvalidSession',
            Op::OP_HELLO           => 'handleHello',
            Op::OP_HEARTBEAT_ACK   => 'handleHeartbeatAck',
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
    public function handleWsClose($op, $reason)
    {
        $this->connected = false;

        if (! is_null($this->heartbeatTimer)) {
            $this->heartbeatTimer->cancel();
            $this->heartbeatTimer = null;
        }

        if (! is_null($this->heartbeatAckTimer)) {
            $this->heartbeatAckTimer->cancel();
            $this->heartbeatAckTimer = null;
        }

        if ($this->closing) {
            return;
        }

        $this->logger->warning('websocket closed', ['op' => $op, 'reason' => $reason]);

        if ($op == Op::CLOSE_INVALID_TOKEN) {
            $this->emit('error', ['token is invalid', $this]);
            $this->logger->error('the token you provided is invalid');

            return;
        }

        ++$this->reconnectCount;
        $this->reconnecting = true;
        $this->logger->info('starting reconnect', ['reconnect_count' => $this->reconnectCount]);
        $this->connectWs();
    }

    /**
     * Handles WebSocket errors received by the client.
     *
     * @param \Exception $e The error.
     */
    public function handleWsError($e)
    {
        // Pawl pls
        if (strpos($e->getMessage(), 'Tried to write to closed stream') !== false) {
            return;
        }

        $this->logger->error('websocket error', ['e' => $e->getMessage()]);
        $this->emit('error', [$e, $this]);

        $this->handleWsClose(0, 'websocket error');
    }

    /**
     * Handles dispatch events received by the WebSocket.
     *
     * @param object $data Packet data.
     */
    protected function handleDispatch($data)
    {
        if (! is_null($hData = $this->handlers->getHandler($data->t))) {
            $handler = new $hData['class'](
                $this->http,
                $this->factory,
                $this->cache,
                $this
            );

            $deferred = new Deferred();
            $deferred->promise()->then(function ($d) use ($data, $hData) {
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
                $this->logger->warning('error while trying to handle dispatch packet', ['packet' => $data->t, 'error' => $e]);
            }, function ($d) use ($data) {
                $this->logger->warning('notified from event', ['data' => $d, 'packet' => $data->t]);
            });

            $parse = [
                Event::GUILD_CREATE,
            ];

            if (! $this->emittedReady && (array_search($data->t, $parse) === false)) {
                $this->unparsedPackets[] = function () use (&$handler, &$deferred, &$data) {
                    $handler->handle($deferred, $data->d);
                };
            } else {
                $handler->handle($deferred, $data->d);
            }
        }

        $handlers = [
            Event::VOICE_SERVER_UPDATE => 'handleVoiceServerUpdate',
            Event::RESUMED             => 'handleResume',
            Event::READY               => 'handleReady',
            Event::GUILD_MEMBERS_CHUNK => 'handleGuildMembersChunk',
            Event::VOICE_STATE_UPDATE  => 'handleVoiceStateUpdate',
        ];

        if (isset($handlers[$data->t])) {
            $this->{$handlers[$data->t]}($data);
        }
    }

    /**
     * Handles heartbeat packets received by the client.
     *
     * @param object $data Packet data.
     */
    protected function handleHeartbeat($data)
    {
        $this->logger->debug('received heartbeat', ['seq' => $data->d]);

        $payload = [
            'op' => Op::OP_HEARTBEAT,
            'd'  => $data->d,
        ];

        $this->send($payload);
    }

    /**
     * Handles heartbeat ACK packets received by the client.
     *
     * @param object $data Packet data.
     */
    protected function handleHeartbeatAck($data)
    {
        $received = microtime(true);
        $diff     = $received - $this->heartbeatTime;
        $time     = $diff * 1000;

        $this->heartbeatAckTimer->cancel();
        $this->emit('heartbeat-ack', [$time, $this]);
        $this->logger->debug('received heartbeat ack', ['response_time' => $time]);
    }

    /**
     * Handles reconnect packets received by the client.
     *
     * @param object $data Packet data.
     */
    protected function handleReconnect($data)
    {
        $this->logger->warning('received opcode 7 for reconnect');

        $this->ws->close(
            Op::CLOSE_NORMAL,
            'gateway redirecting - opcode 7'
        );
    }

    /**
     * Handles invalid session packets received by the client.
     *
     * @param object $data Packet data.
     */
    protected function handleInvalidSession($data)
    {
        $this->logger->warning('invalid session, re-identifying');

        $this->identify(false);
    }

    /**
     * Handles HELLO packets received by the websocket.
     *
     * @param object $data Packet data.
     */
    protected function handleHello($data)
    {
        $this->logger->info('received hello');

        $resume = $this->identify();

        if (! $resume) {
            $this->setupHeartbeat($data->d->heartbeat_interval);
        }
    }

    /**
     * Identifies with the Discord gateway with `IDENTIFY` or `RESUME` packets.
     *
     * @param bool $resume Whether resume should be enabled.
     */
    protected function identify($resume = true)
    {
        if ($resume && $this->reconnecting && ! is_null($this->sessionId)) {
            $payload = [
                'op' => Op::OP_RESUME,
                'd'  => [
                    'session_id' => $this->sessionId,
                    'seq'        => $this->seq,
                    'token'      => $this->token,
                ],
            ];

            $this->logger->info('resuming connection', ['payload' => $payload]);
        } else {
            $payload = [
                'op' => Op::OP_IDENTIFY,
                'd'  => [
                    'token'      => $this->token,
                    'properties' => [
                        '$os'               => PHP_OS,
                        '$browser'          => $this->http->getUserAgent(),
                        '$device'           => $this->http->getUserAgent(),
                        '$referrer'         => 'https://github.com/teamreflex/DiscordPHP',
                        '$referring_domain' => 'https://github.com/teamreflex/DiscordPHP',
                    ],
                    'compress' => true,
                ],
            ];

            if (array_key_exists('shardId', $this->options) &&
                array_key_exists('shardCount', $this->options)) {
                $payload['d']['shard'] = [
                    (int) $this->options['shardId'],
                    (int) $this->options['shardCount'],
                ];
            }

            $this->logger->info('identifying', ['payload' => $payload]);
        }

        $this->send($payload);

        return $payload['op'] == Op::OP_RESUME;
    }

    /**
     * Sends a heartbeat packet to the Discord gateway.
     *
     * @return void
     */
    public function heartbeat()
    {
        $this->logger->debug('sending heartbeat', ['seq' => $this->seq]);

        $payload = [
            'op' => Op::OP_HEARTBEAT,
            'd'  => $this->seq,
        ];

        $this->send($payload);
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
     * @return void
     */
    protected function setupChunking()
    {
        if (! $this->options['loadAllMembers']) {
            $this->logger->info('loadAllMembers option is disabled, not setting chunking up');

            return $this->ready();
        }

        $checkForChunks = function () {
            if ((count($this->largeGuilds) < 1) && (count($this->largeSent) < 1)) {
                $this->ready();

                return;
            }

            $chunks = array_chunk($this->largeGuilds, 50);
            $this->logger->debug('sending '.count($chunks).' chunks with '.count($this->largeGuilds).' large guilds overall');
            $this->largeSent   = array_merge($this->largeGuilds, $this->largeSent);
            $this->largeGuilds = [];

            $sendChunks = function () use (&$sendChunks, &$chunks) {
                $chunk = array_pop($chunks);

                if (is_null($chunk)) {
                    return;
                }

                $this->logger->debug('sending chunk with '.count($chunk).' large guilds');

                $payload = [
                    'op' => Op::OP_GUILD_MEMBER_CHUNK,
                    'd'  => [
                        'guild_id' => $chunk,
                        'query'    => '',
                        'limit'    => 0,
                    ],
                ];

                $this->send($payload);
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
    protected function setupHeartbeat($interval)
    {
        $this->heartbeatInterval = $interval;
        if (isset($this->heartbeatTimer)) {
            $this->heartbeatTimer->cancel();
        }

        $interval             = $interval / 1000;
        $this->heartbeatTimer = $this->loop->addPeriodicTimer($interval, [$this, 'heartbeat']);
        $this->heartbeat();

        $this->logger->info('heartbeat timer initilized', ['interval' => $interval * 1000]);
    }

    /**
     * Initilizes the connection with the Discord gateway.
     *
     * @return void
     */
    protected function connectWs()
    {
        $this->logger->info('starting connection to websocket', ['gateway' => $this->gateway]);

        $this->wsFactory->__invoke($this->gateway)->then(
            [$this, 'handleWsConnection'],
            [$this, 'handleWsError']
        );
    }

    /**
     * Sends a packet to the Discord gateway.
     *
     * @param array $data Packet data.
     */
    protected function send(array $data)
    {
        $json = json_encode($data);

        $this->ws->send($json);
    }

    /**
     * Emits ready if it has not been emitted already.
     *
     * @return void
     */
    protected function ready()
    {
        if ($this->emittedReady) {
            return false;
        }

        $this->logger->info('client is ready');
        $this->emit('ready', [$this]);

        foreach ($this->unparsedPackets as $parser) {
            $parser();
        }
    }

    /**
     * Updates the clients presence.
     *
     * @param Game $game The game object.
     * @param bool $idle Whether we are idle.
     */
    public function updatePresence(Game $game = null, $idle = false)
    {
        $idle = ($idle) ? $idle : null;

        if (! is_null($game)) {
            $game = $game->getPublicAttributes();
        }

        $payload = [
            'op' => Op::OP_PRESENCE_UPDATE,
            'd'  => [
                'game'       => $game,
                'idle_since' => $idle,
            ],
        ];

        $this->send($payload);
    }

    /**
     * Gets a voice client from a guild ID.
     *
     * @param int $id The guild ID to look up.
     *
     * @return \React\Promise\Promise
     */
    public function getVoiceClient($id)
    {
        if (isset($this->voiceClients[$id])) {
            return \React\Promise\resolve($this->voiceClients[$id]);
        }

        return \React\Promise\reject(new \Exception('Could not find the voice client.'));
    }

    /**
     * Joins a voice channel.
     *
     * @param Channel $channel The channel to join.
     * @param bool    $mute    Whether you should be mute when you join the channel.
     * @param bool    $deaf    Whether you should be deaf when you join the channel.
     * @param Monolog $monolog A Monolog logger to use.
     *
     * @return \React\Promise\Promise
     */
    public function joinVoiceChannel(Channel $channel, $mute = false, $deaf = true, $monolog = null)
    {
        $deferred = new Deferred();

        if ($channel->type != Channel::TYPE_VOICE) {
            $deferred->reject(new \Exception('You cannot join a text channel.'));

            return $deferred->promise();
        }

        if (isset($this->voiceClients[$channel->guild_id])) {
            $deferred->reject(new \Exception('You cannot join more than one voice channel per guild.'));

            return $deferred->promise();
        }

        $data = [
            'user_id' => $this->id,
            'deaf'    => $deaf,
            'mute'    => $mute,
        ];

        $voiceStateUpdate = function ($vs, $discord) use ($channel, &$data, &$voiceStateUpdate) {
            if ($vs->guild_id != $channel->guild_id) {
                return; // This voice state update isn't for our guild.
            }

            $data['session'] = $vs->session_id;
            $this->logger->info('received session id for voice sesion', ['guild' => $channel->guild_id, 'session_id' => $vs->session_id]);
            $this->removeListener(Event::VOICE_STATE_UPDATE, $voiceStateUpdate);
        };

        $voiceServerUpdate = function ($vs, $discord) use ($channel, &$data, &$voiceServerUpdate, $deferred, $monolog) {
            if ($vs->guild_id != $channel->guild_id) {
                return; // This voice server update isn't for our guild.
            }

            $data['token']    = $vs->token;
            $data['endpoint'] = $vs->endpoint;
            $this->logger->info('received token and endpoint for voice session', ['guild' => $channel->guild_id, 'token' => $vs->token, 'endpoint' => $vs->endpoint]);

            if (is_null($monolog)) {
                $monolog = new Monolog('Voice-'.$channel->guild_id);
            }

            $logger = new Logger($monolog, $this->options['logging']);
            $vc     = new VoiceClient($this->ws, $this->loop, $channel, $logger, $data);

            $vc->once('ready', function () use ($vc, $deferred, $channel, $logger) {
                $logger->info('voice client is ready');
                $this->voiceClients[$channel->guild_id] = $vc;

                $vc->setBitrate($channel->bitrate)->then(function () use ($vc, $deferred, $logger, $channel) {
                    $logger->info('set voice client bitrate', ['bitrate' => $channel->bitrate]);
                    $deferred->resolve($vc);
                });
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
            'd'  => [
                'guild_id'   => $channel->guild_id,
                'channel_id' => $channel->id,
                'self_mute'  => $mute,
                'self_deaf'  => $deaf,
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
     * @return \React\Promise\Promise
     */
    protected function setGateway($gateway = null)
    {
        $deferred = new Deferred();

        $buildParams = function ($gateway) use ($deferred) {
            $params = [
                'v'        => self::GATEWAY_VERSION,
                'encoding' => $this->encoding,
            ];

            $query         = http_build_query($params);
            $this->gateway = trim($gateway, '/').'/?'.$query;

            $deferred->resolve($this->gateway);
        };

        if (is_null($gateway)) {
            $this->http->get('gateway')->then(function ($response) use ($buildParams) {
                $buildParams($response->url);
            }, function ($e) use ($buildParams) {
                // Can't access the API server so we will use the default gateway.
                $buildParams('wss://gateway.discord.gg');
            });
        } else {
            $buildParams($gateway);
        }

        $deferred->promise()->then(function ($gateway) {
            $this->logger->info('gateway retrieved and set', ['gateway' => $gateway]);
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
     * @return array Options.
     */
    protected function resolveOptions(array $options = [])
    {
        $resolver = new OptionsResolver();
        $logger   = new Monolog('DiscordPHP');

        $resolver
            ->setRequired('token')
            ->setAllowedTypes('token', 'string')
            ->setDefined([
                'token',
                'bot',
                'shardId',
                'shardCount',
                'loop',
                'logger',
                'loggerLevel',
                'logging',
                'cachePool',
                'loadAllMembers',
                'disabledEvents',
                'pmChannels',
                'storeMessages',
                'retrieveBans',
            ])
            ->setDefaults([
                'loop'           => LoopFactory::create(),
                'bot'            => true,
                'logger'         => null,
                'loggerLevel'    => Monolog::INFO,
                'logging'        => true,
                'cachePool'      => new ArrayCachePool(),
                'loadAllMembers' => false,
                'disabledEvents' => [],
                'pmChannels'     => false,
                'storeMessages'  => false,
                'retrieveBans'   => true,
            ])
            ->setAllowedTypes('bot', 'bool')
            ->setAllowedTypes('loop', LoopInterface::class)
            ->setAllowedTypes('logging', 'bool')
            ->setAllowedTypes('cachePool', CacheItemPoolInterface::class)
            ->setAllowedTypes('loadAllMembers', 'bool')
            ->setAllowedTypes('disabledEvents', 'array')
            ->setAllowedTypes('pmChannels', 'bool')
            ->setAllowedTypes('storeMessages', 'bool')
            ->setAllowedTypes('retrieveBans', 'bool');

        $options = $resolver->resolve($options);

        if (is_null($options['logger'])) {
            $logger->pushHandler(new StreamHandler('php://stdout', $options['loggerLevel']));
            $options['logger'] = $logger;
        }

        return $options;
    }

    /**
     * Adds a large guild to the large guild array.
     *
     * @param Guild $guild The guild.
     */
    public function addLargeGuild($guild)
    {
        $this->largeGuilds[] = $guild->id;
    }

    /**
     * Starts the ReactPHP event loop.
     *
     * @return void
     */
    public function run()
    {
        $this->loop->run();
    }

    /**
     * Closes the Discord client.
     *
     * @return void
     */
    public function close()
    {
        $this->closing = true;
        $this->ws->close(1000, 'discordphp closing...');
        $this->emit('closed', [$this]);
    }

    /**
     * Allows access to the part/repository factory.
     *
     * @param …
     *
     * @return mixed
     *
     * @see Factory::create()
     */
    public function factory()
    {
        return call_user_func_array([$this->factory, 'create'], func_get_args());
    }

    /**
     * Attempts to get a repository from the cache.
     *
     * @param string $class The repository to look for.
     * @param string $id    The snowflake to look for.
     * @param string $key   The key to look for.
     * @param array  $vars  An array of args to pass to the repository.
     *
     * @return AbstractRepository
     */
    public function getRepository($class, $id, $key, $vars = [])
    {
        $classKey = str_replace('\\', '', $class);
        $cacheKey = "repositories.{$classKey}.{$id}.{$key}";

        if ($object = $this->cache->get($cacheKey)) {
            return $object;
        }

        $repository = new $class(
            $this->http,
            $this->cache,
            $this->factory,
            $vars
        );

        $this->cache->set($cacheKey, $repository);

        return $repository;
    }

    /**
     * Handles dynamic get calls to the client.
     *
     * @param string $name Variable name.
     *
     * @return mixed
     */
    public function __get($name)
    {
        $allowed = ['loop', 'options', 'logger', 'http'];

        if (array_search($name, $allowed) !== false) {
            return $this->{$name};
        }

        if (is_null($this->client)) {
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
    public function __set($name, $value)
    {
        if (is_null($this->client)) {
            return;
        }

        $this->client->{$name} = $value;
    }

    /**
     * Handles dynamic calls to the client.
     *
     * @param string $name   Function name.
     * @param array  $params Function paramaters.
     *
     * @return mixed
     */
    public function __call($name, $params)
    {
        if (is_null($this->client)) {
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
    public function __debugInfo()
    {
        $secrets = [
            'token' => '*****',
        ];
        $replace = array_intersect_key($secrets, $this->options);
        $config  = $replace + $this->options;

        unset($config['loop'], $config['cachePool'], $config['logger']);

        return $config;
    }
}
