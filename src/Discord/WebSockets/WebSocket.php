<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets;

use Discord\Cache\Cache;
use Discord\Discord;
use Discord\Erlpack\Erlpack;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\Permissions\RolePermission as Permission;
use Discord\Parts\User\Member;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\Voice\VoiceClient;
use Evenement\EventEmitter;
use Ratchet\Client\Connector as WsFactory;
use Ratchet\Client\WebSocket as WebSocketInstance;
use Ratchet\RFC6455\Messaging\Frame;
use React\Dns\Resolver\Factory as DnsFactory;
use React\Dns\Resolver\Resolver;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Stream\Stream;

/**
 * This class is the base for the Discord WebSocket.
 */
class WebSocket extends EventEmitter
{
    /**
     * The current gateway version.
     *
     * @var int THe gateway version.
     */
    const CURRENT_GATEWAY_VERSION = 4;

    /**
     * The WebSocket event loop.
     *
     * @var \React\EventLoop\Factory The Event Loop.
     */
    public $loop;

    /**
     * The WebSocket factory.
     *
     * @var WsFactory The WebSocket factory.
     */
    protected $wsfactory;

    /**
     * The WebSocket instance.
     *
     * @var WebSocketInstance The WebSocket client instance.
     */
    protected $ws;

    /**
     * The Discord instance.
     *
     * @var \Discord\Discord The Discord REST client instance.
     */
    protected $discord;

    /**
     * The Discord WebSocket gateway.
     *
     * @var string The Discord WebSocket gateway.
     */
    protected $gateway;

    /**
     * The event handlers.
     *
     * @var Handlers The Handlers class.
     */
    protected $handlers;

    /**
     * The amount of times that the WebSocket has attempted to reconnect.
     *
     * @var int Reconnect count.
     */
    protected $reconnectCount = 0;

    /**
     * If the WebSocket is reconnecting.
     *
     * @var bool Whether the WebSocket is reconnecting.
     */
    protected $reconnecting = false;

    /**
     * The reconnect reset timer.
     *
     * @var TimerInterface THe reconnect reset timer.
     */
    protected $reconnectResetTimer;

    /**
     * An array of voice clients.
     *
     * @var array Array of voice clients.
     */
    protected $voiceClients = [];

    /**
     * The WebSocket heartbeat.
     *
     * @var TimerInterface The WebSocket heartbeat.
     */
    protected $heartbeat;

    /**
     * The current session ID.
     *
     * @var string The session ID.
     */
    protected $sessionId = '';

    /**
     * The WebSocket message sequence.
     *
     * @var int The sequence.
     */
    protected $seq;

    /**
     * Whether to use ETF.
     *
     * @var bool Whether to use ETF.
     */
    protected $useEtf = true;

    /**
     * The Erlang ETF encoder.
     *
     * @var Erlpack The encoder.
     */
    protected $etf;

    /**
     * Whether we have had an invalid session error.
     *
     * @var bool Invalid session.
     */
    protected $invalidSession = false;

    /**
     * Whether we are being redirected.
     *
     * @var bool Redirected.
     */
    protected $redirecting;

    /**
     * Large servers.
     *
     * @var array Large servers.
     */
    protected $largeServers = [];

    /**
     * Unavailable servers.
     *
     * @var array Unavailable servers.
     */
    protected $unavailableServers = [];

    /**
     * Timer that waits for unavailable servers to come online.
     *
     * @var Timer The timer.
     */
    protected $unavailableTimer;

    /**
     * Whether we have emitted ready.
     *
     * @var bool Emitted ready.
     */
    protected $emittedReady = false;

    /**
     * Constructs the WebSocket instance.
     *
     * @param Discord            $discord  The Discord REST client instance.
     * @param LoopInterface|null $loop     The ReactPHP Event Loop.
     * @param bool               $etf      Whether to use ETF.
     * @param int                $flush    The time interval to flush all message caches. Null if disabled.
     * @param Resolver           $resolver The DNS resolver to use.
     *
     * @return void
     */
    public function __construct(Discord $discord, LoopInterface &$loop = null, $etf = true, $flush = 600, Resolver $resolver = null)
    {
        $this->discord   = $discord;
        $this->gateway   = $this->getGateway();
        $loop            = (is_null($loop)) ? LoopFactory::create() : $loop;
        $resolver        = (is_null($resolver)) ? (new DnsFactory())->create('8.8.8.8', $loop) : $resolver;
        $this->wsfactory = new WsFactory($loop, $resolver);

        // ETF breaks snowflake IDs on 32-bit.
        if (2147483647 !== PHP_INT_MAX) {
            $this->useEtf = $etf;

            if ($etf) {
                $this->etf = new Erlpack();
                $this->etf->on('error', function ($e) {
                    $this->emit('error', [$e, $this]);
                });
            }
        }

        $this->handlers = new Handlers();
        $this->on('ready', function () {
            $this->emittedReady = true;
        });

        $this->wsfactory->__invoke($this->gateway)->then(
            [$this, 'handleWebSocketConnection'],
            [$this, 'handleWebSocketError']
        );

        if (! is_null($flush)) {
            $loop->addPeriodicTimer($flush, function () {
                foreach ($this->discord->guilds as $guild) {
                    foreach ($guild->channels->getAll('type', 'text') as $channel) {
                        $collection = new Collection();
                        $collection->setCacheKey("channel.{$channel->id}.messages", true);
                    }
                }

                $this->emit('messages-flushed', [$this]);
            });
        }

        $this->loop = $loop;
    }

    /**
     * Handles a WebSocket connection.
     *
     * @param WebSocketInstance $ws The WebSocket instance.
     *
     * @return void
     */
    public function handleWebSocketConnection(WebSocketInstance $ws)
    {
        $data = null;

        $ws->on('message', function ($message, $ws) use (&$data) {
            if ($message->isBinary()) {
                if ($this->useEtf) {
                    $data = $this->etf->unpack($message->getPayload());
                    $data = json_encode($data); // terrible hack to convert array -> object
                } else {
                    $data = zlib_decode($message->getPayload());
                }
            } else {
                $data = $message->getPayload();
            }

            $data = json_decode($data);
            $this->emit('raw', [$data, $this->discord]);

            if (isset($data->s) && ! is_null($data->s)) {
                $this->seq = $data->s;
            }

            switch ($data->op) {
                case Op::OP_DISPATCH:
                    if (! is_null($handlerSettings = $this->handlers->getHandler($data->t))) {
                        $this->handleHandler($handlerSettings, $data);
                    }

                    $handlers = [
                        Event::VOICE_SERVER_UPDATE  => 'handleVoiceServerUpdate',
                        Event::RESUMED              => 'handleResume',
                        Event::READY                => 'handleReady',
                        Event::GUILD_MEMBERS_CHUNK  => 'handleGuildMembersChunk',
                        Event::VOICE_STATE_UPDATE   => 'handleVoiceStateUpdate',
                    ];

                    if (isset($handlers[$data->t])) {
                        $this->{$handlers[$data->t]}($data);
                    }
                    break;
                case Op::OP_HEARTBEAT:
                    $this->send([
                        'op' => Op::OP_HEARTBEAT,
                        'd'  => $data->d,
                    ]);
                    break;
                case Op::OP_RECONNECT:
                    $this->ws->close(Op::CLOSE_NORMAL, 'gateway redirecting - opcode 7');
                    break;
                case Op::OP_INVALID_SESSION:
                    $this->sendLoginFrame();
                    break;
            }
        });

        $ws->on('close', function ($op, $reason) {
            if ($op instanceof Stream) {
                $op = Op::CLOSE_ABNORMAL;
                $reason = 'PHP Stream closed.';
            }

            $this->emit('close', [$op, $reason, $this->discord]);

            if (! is_null($this->heartbeat)) {
                $this->loop->cancelTimer($this->heartbeat);
            }

            if ($this->redirecting) {
                $this->emit('redirecting', [$this->endpoint, $this]);
                $this->redirecting = false;
                $this->reconnecting = true;
                $this->wsfactory->__invoke($this->gateway)->then([$this, 'handleWebSocketConnection'], [$this, 'handleWebSocketError']);

                return;
            }

            if ($this->reconnectCount >= 4) {
                $this->emit('ws-reconnect-max', [$this->discord]);
                $this->loop->stop();

                return;
            }

            // Invalid Session
            if ($op == Op::CLOSE_INVALID_SESSION && strpos($reason, 'invalid session') !== false) {
                $this->emit('invalid-session', [$this->discord, $this]);
                $this->wsfactory->__invoke($this->gateway)->then([$this, 'handleWebSocketConnection'], [$this, 'handleWebSocketError']);
                ++$this->reconnectCount;

                return;
            }

            if (! $this->reconnecting) {
                $this->emit('reconnecting', [$this->discord, $this]);

                if ($this->emittedReady) {
                    $this->reconnecting = true;
                }

                $this->gateway      = $this->getGateway();
                $this->wsfactory->__invoke($this->gateway)->then([$this, 'handleWebSocketConnection'], [$this, 'handleWebSocketError']);
                ++$this->reconnectCount;
            }
        });

        $ws->on('error', function ($error, $ws) {
            $this->emit('error', [$error, $ws, $this->discord, $this]);
        });

        $this->ws = $ws;

        if ($this->reconnecting && is_null($this->sessionId)) {
            $this->send([
                'op' => Op::OP_RESUME,
                'd'  => [
                    'session_id' => $this->sessionId,
                    'seq'        => $this->seq,
                ],
            ]);
        } else {
            $this->sendLoginFrame();
        }
    }

    /**
     * Handles a WebSocket error.
     *
     * @param \Exception $e The error.
     *
     * @return void
     */
    public function handleWebSocketError($e)
    {
        $this->emit('ws-connect-error', [$e]);
    }

    /**
     * Handles `RESUME` frames.
     *
     * @param array $data The WebSocket data.
     *
     * @return void
     */
    public function handleResume($data)
    {
        $tts = $data->d->heartbeat_interval / 1000;
        $this->heartbeat();
        $this->heartbeat = $this->loop->addPeriodicTimer($tts / 4, [$this, 'heartbeat']);

        $this->emit('reconnected', [$this]);
    }

    /**
     * Handles `VOICE_SERVER_UPDATE` frames.
     *
     * @param array $data The WebSocket data.
     *
     * @return void
     */
    public function handleVoiceServerUpdate($data)
    {
        if (isset($this->voiceClients[$data->d->guild_id])) {
            $this->voiceClients[$data->d->guild_id]->handleVoiceServerChange((array) $data->d);
        }
    }

    /**
     * Handles `READY` frames.
     *
     * @param array $data The WebSocket data.
     *
     * @return void
     */
    public function handleReady($data)
    {
        if (! is_null($this->reconnectResetTimer)) {
            $this->loop->cancelTimer($this->reconnectResetTimer);
        }

        $this->reconnectResetTimer = $this->loop->addTimer(60 * 2, function () {
            $this->reconnectCount = 0;
        });

        $tts = $data->d->heartbeat_interval / 1000;
        $this->heartbeat();
        $this->heartbeat = $this->loop->addPeriodicTimer($tts / 4, [$this, 'heartbeat']);

        // don't want to reparse ready
        if ($this->reconnecting) {
            $this->reconnecting = false;

            return;
        }

        $content = $data->d;

        $this->emit('trace', $content->_trace);

        // guilds
        $guilds = new Collection();

        foreach ($content->guilds as $guild) {
            if (isset($guild->unavailable)) {
                $this->emit('unavailable', ['READY', $guild->id, $this]);
                $this->unavailableServers[$guild->id] = $guild->id;

                continue;
            }

            $guildPart = new Guild((array) $guild, true);

            $channels = new Collection();

            foreach ($guild->channels as $channel) {
                $channel             = (array) $channel;
                $channel['guild_id'] = $guild->id;
                $channelPart         = new Channel($channel, true);

                $channels->push($channelPart);

                Cache::set("channel.{$channelPart->id}", $channelPart);
            }

            $channels->setCacheKey("guild.{$guild->id}.channels", true);
            unset($channels);

            // guild members
            $members = new Collection();

            foreach ($guild->members as $member) {
                $member             = (array) $member;
                $member['guild_id'] = $guild->id;
                $member['status']   = 'offline';
                $member['game']     = null;
                $memberPart         = new Member($member, true);

                // check for presences

                foreach ($guild->presences as $presence) {
                    if ($presence->user->id == $member['user']->id) {
                        $memberPart->status = $presence->status;
                        $memberPart->game   = $presence->game;
                    }
                }

                // Since when we use GUILD_MEMBERS_CHUNK, we have to cycle through the current members
                // and see if they exist already. That takes ~34ms per member, way way too much.
                $members[$memberPart->id] = $memberPart;

                // Cache::set("guild.{$memberPart->guild_id}.members.{$memberPart->id}", $memberPart);
            }

            $members->setCacheKey("guild.{$guild->id}.members", true);
            unset($members);

            // guild roles
            $roles = new Collection();

            foreach ($guild->roles as $role) {
                $perm = new Permission([
                    'perms' => $role->permissions,
                ]);

                $role                = (array) $role;
                $role['guild_id']    = $guild->id;
                $role['permissions'] = $perm;
                $rolePart            = new Role($role, true);

                $roles->push($rolePart);

                Cache::set("roles.{$rolePart->id}", $rolePart);
            }

            $roles->setCacheKey("guild.{$guild->id}.roles", true);
            unset($roles);

            $guilds->push($guildPart);

            if ($guildPart->large) {
                $this->largeServers[$guildPart->id] = $guildPart->id;
            }

            // voice states
            foreach ($guild->voice_states as $state) {
                if ($channel = $guildPart->channels->get('id', $state->channel_id)) {
                    $channel->members[$state->user_id] = new VoiceStateUpdate((array) $state, true);
                }
            }

            Cache::set("guild.{$guildPart->id}", $guildPart);
        }

        $this->discord->setCache('guilds', $guilds);
        unset($guilds);

        $this->sessionId = $content->session_id;

        // unavailable servers
        if (count($this->unavailableServers) > 0) {
            $this->unavailableTimer = $this->loop->addTimer(60 * 2, function ($timer) {
                if ($this->emittedReady) {
                    $timer->cancel();

                    return;
                }

                $this->emit('ready', [$this->discord, $this]);
            });

            $handleGuildCreate = function ($guild) use (&$handleGuildCreate) {
                if (! isset($this->unavailableServers[$guild->id])) {
                    return;
                }

                unset($this->unavailableServers[$guild->id]);
                $this->emit('available', [$guild->id, $this]);

                if (count($this->unavailableServers) < 1) {
                    $this->loop->cancelTimer($this->unavailableTimer);
                    $servers = array_values($this->largeServers);

                    if (count($servers) < 1) {
                        $this->removeListener(Event::GUILD_CREATE, $handleGuildCreate);

                        if (! $this->invalidSession && ! $this->emittedReady) {
                            $this->emit('ready', [$this->discord, $this]);
                        } else {
                            $this->invalidSession = false;
                        }

                        return;
                    }

                    $chunks = array_chunk($servers, 50);

                    $sendChunk = function () use (&$sendChunk, &$chunks) {
                        $chunk = array_pop($chunks);

                        // We have finished our chunks
                        if (is_null($chunk)) {
                            return;
                        }

                        $this->send([
                            'op' => Op::OP_GUILD_MEBMER_CHUNK,
                            'd'  => [
                                'guild_id' => $chunk,
                                'query'    => '',
                                'limit'    => 0,
                            ],
                        ]);

                        $this->loop->addTimer(1, $sendChunk);
                    };

                    $sendChunk();
                }
            };

            $this->on(Event::GUILD_CREATE, $handleGuildCreate);
        } else {
            if (! $this->emittedReady) {
                $this->emit('ready', [$this->discord, $this]);
            }
        }
    }

    /**
     * Handles `VOICE_STATE_UPDATE` frames.
     *
     * @param array $data The WebSocket data.
     *
     * @return void
     */
    public function handleVoiceStateUpdate($data)
    {
        if (isset($this->voiceClients[$data->d->guild_id])) {
            $this->voiceClients[$data->d->guild_id]->handleVoiceStateUpdate($data->d);
        }
    }

    /**
     * Handles `GUILD_MEMBERS_CHUNK` frames.
     *
     * @param array $data The WebSocket data.
     *
     * @return void
     */
    public function handleGuildMembersChunk($data)
    {
        $members = $data->d->members;

        foreach ($this->discord->guilds as $index => $guild) {
            if ($guild->id == $data->d->guild_id) {
                if (is_null($guild)) {
                    return;
                }

                $memberColl = $guild->members;
                $memberColl->setCacheKey(null, false);

                foreach ($members as $member) {
                    if (isset($memberColl[$member->user->id])) {
                        continue;
                    }

                    $member             = (array) $member;
                    $member['guild_id'] = $data->d->guild_id;
                    $member['status']   = 'offline';
                    $member['game']     = null;
                    $memberPart         = new Member($member, true);

                    $memberColl[$memberPart->id] = $memberPart;
                }

                $memberColl->setCacheKey("guild.{$guild->id}.members", true);

                if ($memberColl->count() == $guild->member_count) {
                    if (isset($this->largeServers[$data->d->guild_id])) {
                        unset($this->largeServers[$data->d->guild_id]);
                    }

                    $this->emit('guild-ready', [$guild, $this]);
                }

                unset($memberColl);

                if ($this->largeServers === true) {
                    break;
                }

                if (count($this->largeServers) === 0 && ! $this->emittedReady) {
                    $this->loop->addPeriodicTimer(5, function () {
                        if (is_array($this->largeServers) && count($this->largeServers) > 0) {
                            $servers = array_values($this->largeServers);
                            $this->largeServers = [];
                            $chunks  = array_chunk($servers, 50);

                            $sendChunk = function () use (&$sendChunk, &$chunks) {
                                $chunk = array_pop($chunks);

                                // We have finished our chunks
                                if (is_null($chunk)) {
                                    return;
                                }

                                $this->send([
                                    'op' => Op::OP_GUILD_MEBMER_CHUNK,
                                    'd'  => [
                                        'guild_id' => $chunk,
                                        'query'    => '',
                                        'limit'    => 0,
                                    ],
                                ]);

                                $this->loop->addTimer(1, $sendChunk);
                            };

                            $sendChunk();
                        }
                    });
                    $this->largeServers = true;
                    if (! $this->emittedReady) {
                        $this->emit('ready', [$this->discord, $this]);
                    }
                }

                break;
            }
        }

        unset($members);
    }

    /**
     * Handles frames with an opcode of 7.
     *
     * @param array $data The WebSocket data.
     *
     * @return void
     */
    public function handleOp7()
    {
        $this->redirecting = true;
        $ws->close();
    }

    /**
     * Handles and emits events with handlers.
     *
     * @param array $handlerSettings The handler to call.
     * @param array $data            The WebSocket data.
     *
     * @return void
     */
    public function handleHandler($handlerSettings, $data)
    {
        $handler     = new $handlerSettings['class']();

        $handler->on('unavailable', function ($id) use ($handlerSettings) {
            $this->emit('unavailable', [$handlerSettings['class'], $id, $this]);
        });

        $handler->on('large', function ($guild) {
            if (! is_array($this->largeServers)) {
                $this->largeServers = [];
            }

            $this->largeServers[$guild->id] = $guild->id;
        });

        $handler->on('send-packet', [$this, 'send']);

        $handlerData = $handler->getData($data->d, $this->discord);
        $newDiscord  = $handler->updateDiscordInstance($handlerData, $this->discord);
        $this->emit($data->t, [$handlerData, $this->discord, $newDiscord, $this]);

        foreach ($handlerSettings['alternatives'] as $alternative) {
            $this->emit($alternative, [$handlerData, $this->discord, $newDiscord, $this]);
        }

        if ($data->t == Event::MESSAGE_CREATE && (strpos($handlerData->content, '<@'.$this->discord->id.'>') !== false)) {
            $this->emit('mention', [$handlerData, $this->discord, $newDiscord, $this]);
        }

        $this->discord = $newDiscord;
        unset($handler, $handlerData, $newDiscord, $handlerSettings);
    }

    /**
     * Runs a heartbeat.
     *
     * @return void
     */
    public function heartbeat()
    {
        $this->send([
            'op' => Op::OP_HEARTBEAT,
            'd'  => $this->seq,
        ]);

        $this->emit('heartbeat', [$this->seq, $this]);
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
        $arr      = ['user_id' => $this->discord->id, 'deaf' => $deaf, 'mute' => $mute];

        if ($channel->type != Channel::TYPE_VOICE) {
            $deferred->reject(new \Exception('You cannot join a Text channel.'));

            return $deferred->promise();
        }

        if (isset($this->voiceClients[$channel->guild_id])) {
            $deferred->reject(new \Exception('You cannot join more than one voice channel per guild.'));

            return $deferred->promise();
        }

        $closure = function ($message) use (&$closure, &$arr, $deferred, $channel) {
            if ($message->isBinary()) {
                if ($this->useEtf) {
                    $data = $this->etf->unpack($message->getPayload());
                    $data = json_encode($data); // terrible hack to convert array -> object
                } else {
                    $data = zlib_decode($message->getPayload());
                }
            } else {
                $data = $message->getPayload();
            }

            $data = json_decode($data);

            if ($data->t == Event::VOICE_STATE_UPDATE) {
                if ($data->d->guild_id != $channel->guild_id) {
                    return;
                }

                $arr['session'] = $data->d->session_id;
            } elseif ($data->t == Event::VOICE_SERVER_UPDATE) {
                if ($data->d->guild_id != $channel->guild_id) {
                    return;
                }

                $arr['token']    = $data->d->token;
                $arr['endpoint'] = $data->d->endpoint;

                $vc = new VoiceClient($this, $this->loop, $channel, $arr);
                $vc->once('ready', function () use ($vc, $deferred, $channel) {
                    $vc->setBitrate($channel->bitrate)->then(function () use ($vc, $deferred) {
                        $deferred->resolve($vc);
                    });
                });
                $vc->once('error', function ($e) use ($deferred) {
                    $deferred->reject($e);
                });
                $vc->once('close', function () use ($channel) {
                    unset($this->voiceClients[$channel->guild_id]);
                });
                $this->voiceClients[$channel->guild_id] = $vc;

                $this->ws->removeListener('message', $closure);
            }
        };

        $this->ws->on('message', $closure);

        $this->send([
            'op' => Op::OP_VOICE_STATE_UPDATE,
            'd'  => [
                'guild_id'   => $channel->guild_id,
                'channel_id' => $channel->id,
                'self_mute'  => $mute,
                'self_deaf'  => $deaf,
            ],
        ]);

        return $deferred->promise();
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
     * Runs the Event Loop.
     *
     * @return void
     */
    public function run()
    {
        $this->loop->run();
    }

    /**
     * Sends the login frame to the WebSocket.
     *
     * @return void
     */
    public function sendLoginFrame()
    {
        $token = (substr(DISCORD_TOKEN, 0, 4) === 'Bot ') ? substr(DISCORD_TOKEN, 4) : DISCORD_TOKEN;

        $this->send([
            'op' => Op::OP_IDENTIFY,
            'd'  => [
                'token'      => $token,
                'properties' => [
                    '$os'               => PHP_OS,
                    '$browser'          => Guzzle::getUserAgent(),
                    '$device'           => '',
                    '$referrer'         => 'https://github.com/teamreflex/DiscordPHP',
                    '$referring_domain' => 'https://github.com/teamreflex/DiscordPHP',
                ],
                'large_threshold' => 250,
                'compress'        => true,
            ],
        ]);
    }

    /**
     * Sends data over the WebSocket.
     *
     * @param array $data Data to send to the WebSocket.
     *
     * @return void
     */
    public function send($data)
    {
        if ($this->useEtf) {
            $etf   = $this->etf->pack($data);
            $frame = new Frame($etf, true, 2);
        } else {
            $json  = json_encode($data);
            $frame = new Frame($json, true, 1);
        }

        $this->ws->send($frame);
    }

    /**
     * Gets the WebSocket gateway.
     *
     * @return string The Discord WebSocket gateway.
     */
    public function getGateway()
    {
        return 'wss://gateway.discord.gg?v='.self::CURRENT_GATEWAY_VERSION.'&encoding='.($this->useEtf ? 'etf' : 'json');
    }
}
