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
use Discord\Logging\Logger;
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
use Monolog\Logger as Monolog;
use Psr\Cache\CacheItemPoolInterface;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Discord
{
    use EventEmitterTrait;

    const GATEWAY_VERSION = 5;
    const VERSION = 'v4.0.0-develop';

    protected $logger;
    protected $voiceLoggers = [];
    protected $options;
    protected $token;
    protected $loop;
    protected $wsFactory;
    protected $ws;
    protected $handlers;
    protected $seq;
    protected $reconnecting = false;
    protected $sessionId;
    protected $voiceClients = [];
    protected $largeGuilds = [];
    protected $largeSent = [];
    protected $unparsedPackets = [];
    protected $reconnectCount = 0;
    protected $heatbeatTimer;
    protected $emittedReady = false;
    protected $gateway;
    protected $encoding = 'json';
    protected $http;
    protected $factory;
    protected $cache;
    protected $cachePool;
    protected $client;

    /**
     * Creates a Discord client instance.
     *
     * @param array $options Array of options.
     *
     * @return void
     */
    public function __construct(array $options = [])
    {
        $options = $this->resolveOptions($options);

        $this->token = $options['token'];
        $this->loop = $options['loop'];
        $this->logger = new Logger($options['logger'], $options['logging']);
        $this->wsFactory = new Connector($this->loop);
        $this->handlers = new Handlers();
        $this->cachePool = $options['cachePool'];

        $this->on('ready', function () {
            $this->emittedReady = true;
        });

        $this->options = $options;

        $this->cache = new CacheWrapper($this->cachePool); // todo cache pool
        $this->http = new Http(
            $this->cache,
            $this->token,
            self::VERSION,
            new Guzzle($this->cache, $this->loop)
        );
        $this->factory = new Factory($this->http, $this->cache);

        $this->setGateway()->then(function ($g) {
            $this->connectWs();
        });
    }

    protected function handleVoiceServerUpdate($data)
    {
        if (isset($this->voiceClients[$data->d->guild_id])) {
            $this->logger->debug('voice server update recieved', ['guild' => $data->d->guild_id, 'data' => $data->d]);
            $this->voiceClients[$data->d->guild_id]->handleVoiceServerChange((array) $data->d);
        }
    }

    protected function handleResume($data)
    {
        $this->setupHeartbeat($data->d->heartbeat_interval);

        $this->logger->debug('websocket reconnected to discord');
        $this->emit('reconnected', [$this]);
    }

    protected function handleReady($data)
    {
        $this->logger->debug('ready packet recieved');

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
        $this->logger->debug('discord trace recieved', ['trace' => $content->_trace]);

        // Setup the user account
        $this->client = $this->factory->create(Client::class, $content->user, true);
        $this->sessionId = $content->session_id;

        $this->logger->debug('client created and session id stored', ['session_id' => $content->session_id, 'client' => $this->client->getPublicAttributes()]);

        // Private Channels
        $private_channels = new PrivateChannelRepository(
            $this->http,
            $this->cache,
            $this->factory
        );

        foreach ($content->private_channels as $channel) {
            $channelPart = $this->factory->create(Channel::class, $channel, true);
            $this->cache->set("channels.{$channelPart->id}", $channelPart);
            $this->cache->set("pm_channels.{$channelPart->recipient->id}", $channelPart);
            $private_channels->push($channelPart);
        }

        $this->private_channels = $private_channels;
        $this->logger->debug('stored private channels', ['count' => $private_channels->count()]);

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

        $this->logger->debug('stored guilds', ['count' => $this->guilds->count()]);

        if (count($unavailable) < 1) {
            return $this->ready();
        }

        $function = function ($guild) use (&$function, &$unavailable) {
            if (array_key_exists($guild->id, $unavailable)) {
                unset($unavailable[$guild->id]);
            }

            // todo setup timer to continue after x amount of time
            if (count($unavailable) < 1) {
                $this->logger->debug('all guilds are now available', ['count' => $this->guilds->count()]);
                $this->removeListener(Event::GUILD_CREATE, $function);

                $this->setupChunking();
            }
        };

        $this->on(Event::GUILD_CREATE, $function);
    }

    protected function handleGuildMembersChunk($data)
    {
        $guild = $this->guilds->get('id', $data->d->guild_id);
        $members = $data->d->members;

        $this->logger->debug('recieved guild member chunk', ['guild_id' => $guild->id, 'guild_name' => $guild->name, 'member_count' => count($members)]);

        $count = 0;

        foreach ($members as $member) {
            if (array_key_exists($member->user->id, $guild->members)) {
                continue;
            }

            $member = (array) $member;
            $member['guild_id'] = $guild->id;
            $member['status'] = 'offline';
            $member['game'] = null;

            $memberPart = $this->factory->create(Member::class, $member, true);
            $this->cache->set("guild.{$guild->id}.members.{$memberPart->id}", $memberPart);
            $this->cache->set("user.{$memberPart->id}", $memberPart->user);
            $guild->members->push($memberPart);
            ++$count;
        }

        $this->logger->debug('parsed '.$count.' members', ['repository_count' => $guild->members->count(), 'actual_count' => $guild->member_count]);

        if ($guild->members->count() == $guild->member_count) {
            if (($key = array_search($guild->id, $this->largeSent)) !== false) {
                unset($this->largeSent[$key]);
            }

            $this->logger->debug('all users have been loaded', ['guild' => $guild->id, 'member_collection' => $guild->members->count(), 'member_count' => $guild->member_count]);
        }

        if (count($this->largeSent) < 1) {
            $this->ready();
        }
    }

    protected function handleVoiceStateUpdate($data)
    {
        if (isset($this->voiceClients[$data->d->guild_id])) {
            $this->logger->debug('voic state update recieved', ['guild' => $data->d->guild, 'data' => $data->d]);
            $this->voiceClients[$data->d->guild_id]->handleVoiceStateUpdate($data->d);
        }
    }

    public function handleWsConnection(WebSocket $ws)
    {
        $this->ws = $ws;

        $this->logger->debug('websocket connection has been created');

        $ws->on('message', [$this, 'handleWsMessage']);
        $ws->on('close', [$this, 'handleWsClose']);
        $ws->on('error', [$this, 'handleWsError']);
    }

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
            Op::OP_DISPATCH => 'handleDispatch',
            Op::OP_HEARTBEAT => 'handleHeartbeat',
            Op::OP_RECONNECT => 'handleReconnect',
            Op::OP_INVALID_SESSION => 'handleInvalidSession',
            Op::OP_HELLO => 'handleHello',
        ];

        if (isset($op[$data->op])) {
            $this->{$op[$data->op]}($data);
        }
    }

    public function handleWsClose($op, $reason)
    {
        $this->logger->warning('websocket closed', ['op' => $op, 'reason' => $reason]);

        if ($op == Op::CLOSE_INVALID_TOKEN) {
            $this->emit('error', ['token is invalid', $this]);
            $this->logger->error('the token you provided is invalid');

            return;
        }

        ++$this->reconnectCount;
        $this->reconnecting = true;
        $this->logger->debug('starting reconnect', ['reconnect_count' => $this->reconnectCount]);
        $this->connectWs();
    }

    public function handleWsError($e)
    {
        $this->logger->error('websocket error', ['e' => $e->getMessage()]);
        $this->emit('error', [$e, $this]);
    }

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
                $this->emit($data->t, [$d, $this]);

                foreach ($hData['alternatives'] as $alternative) {
                    $this->emit($alternative, [$d, $this]);
                }
            }, function ($e) use ($data) {
                $this->logger->debug('error while trying to handle dispatch packet', ['packet' => $data->t, 'error' => $e]);
            });

            $parse = [
                Event::GUILD_CREATE,
            ];

            if (! $this->emittedReady && (array_search($data->t, $parse) === false)) {
                $this->logger->debug('client not ready, not parsing', ['event' => $data->t]);

                $this->unparsedPackets[] = function () use (&$handler, &$deferred, &$data) {
                    $handler->handle($deferred, $data->d);
                };
            } else {
                $handler->handle($deferred, $data->d);
            }
        }

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
    }

    protected function handleHeartbeat($data)
    {
        $this->logger->debug('recieved heartbeat', ['seq' => $data->d]);

        $payload = [
            'op' => Op::OP_HEARTBEAT,
            'd' => $data->d,
        ];

        $this->send($payload);
    }

    protected function handleReconnect($data)
    {
        $this->logger->debug('recieved opcode 7 for reconnect');

        $this->ws->close(
            Op::CLOSE_NORMAL,
            'gateway redirecting - opcode 7'
        );
    }

    protected function handleInvalidSession($data)
    {
        $this->logger->debug('invalid session, re-identifying');

        $this->identify(false);
    }

    protected function handleHello($data)
    {
        $this->logger->debug('recieved hello');

        $this->identify();

        $this->setupHeartbeat($data->d->heartbeat_interval);
    }

    protected function identify($resume = true)
    {
        if ($resume && $this->reconnecting && ! is_null($this->sessionId)) {
            $payload = [
                'op' => Op::OP_RESUME,
                'd' => [
                    'session_id' => $this->sessionId,
                    'seq' => $this->seq,
                    'token' => $this->token,
                ],
            ];

            $this->logger->debug('resuming connection', ['payload' => $payload]);
        } else {
            $payload = [
                'op' => Op::OP_IDENTIFY,
                'd' => [
                    'token' => $this->token,
                    'properties' => [
                        '$os' => PHP_OS,
                        '$browser' => $this->http->getUserAgent(),
                        '$device' => $this->http->getUserAgent(),
                        '$referrer' => 'https://github.com/teamreflex/DiscordPHP',
                        '$referring_domain' => 'https://github.com/teamreflex/DiscordPHP',
                    ],
                    'compress' => true,
                ],
            ];

            if (array_key_exists('shardId', $this->options) &&
                array_key_exists('shardCount', $this->options)) {
                $payload['d']['shard'] = [
                    (int) $options['shardId'],
                    (int) $pptions['shardCount'],
                ];
            }

            $this->logger->debug('identifying', ['payload' => $payload]);
        }

        $this->send($payload);
    }

    public function heartbeat()
    {
        $this->logger->debug('sending heartbeat', ['seq' => $this->seq]);

        $payload = [
            'op' => Op::OP_HEARTBEAT,
            'd' => $this->seq,
        ];

        $this->send($payload);
        $this->emit('heartbeat', [$this->seq, $this]);
    }

    protected function setupChunking()
    {
        if (! $this->options['loadAllMembers']) {
            $this->logger->debug('loadAllMembers option is disabled, not setting chunking up');

            return $this->ready();
        }

        $checkForChunks = function () {
            if ((count($this->largeGuilds) < 1) && (count($this->largeSent) < 1)) {
                $this->ready();

                return;
            }

            $chunks = array_chunk($this->largeGuilds, 50);
            $this->logger->debug('sending '.count($chunks).' chunks with '.count($this->largeGuilds).' large guilds overall');
            $this->largeSent = array_merge($this->largeGuilds, $this->largeSent);
            $this->largeGuilds = [];

            $sendChunks = function () use (&$sendChunks, &$chunks) {
                $chunk = array_pop($chunks);

                if (is_null($chunk)) {
                    $this->logger->debug('finished sending chunks');

                    return;
                }

                $this->logger->debug('sending chunk with '.count($chunk).' large guilds');

                $payload = [
                    'op' => Op::OP_GUILD_MEMBER_CHUNK,
                    'd' => [
                        'guild_id' => $chunk,
                        'query' => '',
                        'limit' => 0,
                    ],
                ];

                $this->send($payload);
                $this->loop->addTimer(1, $sendChunks);
            };

            $sendChunks();
        };

        $this->loop->addPeriodicTimer(5, $checkForChunks);
        $this->logger->debug('set up chunking, checking for chunks every 5 seconds');
        $checkForChunks();
    }

    protected function setupHeartbeat($interval)
    {
        if (isset($this->heartbeatTimer)) {
            $this->heartbeatTimer->cancel();
        }

        $interval = $interval / 1000;
        $this->heartbeatTimer = $this->loop->addPeriodicTimer($interval, [$this, 'heartbeat']);
        $this->heartbeat();

        $this->logger->debug('heartbeat timer initilized', ['interval' => $interval * 1000]);
    }

    protected function connectWs()
    {
        $this->logger->debug('starting connection to websocket', ['gateway' => $this->gateway]);

        $this->wsFactory->__invoke($this->gateway)->then(
            [$this, 'handleWsConnection'],
            [$this, 'handleWsError']
        );
    }

    protected function send(array $data)
    {
        $json = json_encode($data);

        $this->ws->send($json);
    }

    protected function ready()
    {
        if ($this->emittedReady) {
            return false;
        }

        $this->logger->debug('client is ready');
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
     *
     * @return void
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
     *
     * @return \React\Promise\Promise
     */
    public function joinVoiceChannel(Channel $channel, $mute = false, $deaf = false)
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
            $this->logger->debug('recieved session id for voice sesion', ['guild' => $channel->guild_id, 'session_id' => $vs->session_id]);
            $this->removeListener(Event::VOICE_STATE_UPDATE, $voiceStateUpdate);
        };

        $voiceServerUpdate = function ($vs, $discord) use ($channel, &$data, &$voiceServerUpdate, $deferred) {
            if ($vs->guild_id != $channel->guild_id) {
                return; // This voice server update isn't for our guild.
            }

            $data['token'] = $vs->token;
            $data['endpoint'] = $vs->endpoint;
            $this->logger->debug('recieved token and endpoint for voic session', ['guild' => $channel->guild_id, 'token' => $vs->token, 'endpoint' => $vs->endpoint]);

            $monolog = new Monolog('Voice-'.$channel->guild_id);
            $logger  = new Logger($monolog, $this->options['logging']); 
            $vc      = new VoiceClient($this, $this->loop, $channel, $logger, $data);

            $vc->once('ready', function () use ($vc, $deferred, $channel, $logger) {
                $logger->debug('voice client is ready');

                $vc->setBitrate($channel->bitrate)->then(function () use ($vc, $deferred, $logger, $channel) {
                    $logger->debug('set voice client bitrate', ['bitrate' => $channel->bitrate]);
                    $deferred->resolve($vc);
                });
            });
            $vc->once('error', function ($e) use ($deferred, $logger) {
                $logger->error('error initilizing voice client', ['e' => $e->getMessage()]);
                $deferred->reject($e);
            });
            $vc->once('close', function () use ($channel, $logger) {
                $logger->debug('voice client closed');
                unset($this->voiceClients[$channel->guild_id]);
            });

            $this->voiceLoggers[$channel->guild_id] = $logger;
            $this->voiceClients[$channel->guild_id] = $vc;
            $this->removeListener(Event::VOICE_SERVER_UPDATE, $voiceServerUpdate);
        };

        $this->on(Event::VOICE_STATE_UPDATE, $voiceStateUpdate);
        $this->on(Event::VOICE_SERVER_UPDATE, $voiceServerUpdate);

        return $deferred->promise();
    }

    protected function setGateway($gateway = null)
    {
        $deferred = new Deferred();

        $buildParams = function ($gateway) use ($deferred) {
            $params = [
                'v' => self::GATEWAY_VERSION,
                'encoding' => $this->encoding,
            ];

            $query = http_build_query($params);
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
            $this->logger->debug('gateway retrieved and set', ['gateway' => $gateway]);
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

        $resolver
            ->setRequired('token')
            ->setAllowedTypes('token', 'string')
            ->setDefined([
                'token',
                'shardId',
                'shardCount',
                'loop',
                'logger',
                'logging',
                'cachePool',
                'loadAllMembers',
            ])
            ->setDefaults([
                'loop' => LoopFactory::create(),
                'logger' => new Monolog('DiscordPHP'),
                'logging' => true,
                'cachePool' => new ArrayCachePool(),
                'loadAllMembers' => false,
            ])
            ->setAllowedTypes('loop', LoopInterface::class)
            ->setAllowedTypes('logger', Monolog::class)
            ->setAllowedTypes('logging', 'bool')
            ->setAllowedTypes('cachePool', CacheItemPoolInterface::class)
            ->setAllowedTypes('loadAllMembers', 'bool');

        return $resolver->resolve($options);
    }

    public function addLargeGuild($guild)
    {
        $this->largeGuilds[] = $guild->id;
    }

    public function run()
    {
        $this->loop->run();
    }

    public function factory()
    {
        return call_user_func_array([$this->factory, 'create'], func_get_args());
    }

    public function __get($name)
    {
        if (is_null($this->client)) {
            return;
        }

        return $this->client->{$name};
    }

    public function __set($name, $value)
    {
        if (is_null($this->client)) {
            return;
        }

        $this->client->{$name} = $value;
    }

    public function __call($name, $params)
    {
        if (is_null($this->client)) {
            return;
        }

        return call_user_func_array([$this->client, $name], $params);
    }
}
