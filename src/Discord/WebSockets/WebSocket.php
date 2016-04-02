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

use Discord\Discord;
use Discord\Erlpack\Erlpack;
use Discord\Factory\PartFactory;
use Discord\Http\Guzzle;
use Discord\Http\Http;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\User\Member;
use Discord\Repository\GuildRepository;
use Discord\Repository\Guild\ChannelRepository;
use Discord\Repository\Guild\MemberRepository;
use Discord\Repository\Guild\RoleRepository;
use Discord\Voice\VoiceClient;
use Discord\Wrapper\CacheWrapper;
use Evenement\EventEmitter;
use Ratchet\Client\Connector as WsFactory;
use Ratchet\Client\WebSocket as WebSocketInstance;
use Ratchet\RFC6455\Messaging\Frame;
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
     * @var Http
     */
    protected $http;

    /**
     * @var PartFactory
     */
    protected $partFactory;

    /**
     * @var CacheWrapper
     */
    protected $cache;

    /**
     * @var string
     */
    protected $token;

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
     * Constructs the WebSocket instance.
     *
     * @param Discord            $discord     The Discord REST client instance.
     * @param Http               $http        The Guzzle Instance
     * @param PartFactory        $partFactory
     * @param CacheWrapper       $cache
     * @param string             $token
     * @param LoopInterface|null $loop        The ReactPHP Event Loop.
     * @param bool               $etf         Whether to use ETF.
     */
    public function __construct(
        Discord $discord,
        Http $http,
        PartFactory $partFactory,
        CacheWrapper $cache,
        $token,
        LoopInterface &$loop = null,
        $etf = false
    ) {
        $this->discord     = $discord;
        $this->http        = $http;
        $this->partFactory = $partFactory;
        $this->cache       = $cache;
        $this->token       = $token;
        $loop              = (is_null($loop)) ? LoopFactory::create() : $loop;
        $this->wsfactory   = new WsFactory($loop);

        $this->getGateway();

        // ETF breaks snowflake IDs on 32-bit.
        if (2147483647 !== PHP_INT_MAX) {
            $this->useEtf = $etf;

            if ($etf) {
                $this->etf = new Erlpack();
                $this->etf->on(
                    'error',
                    function ($e) {
                        $this->emit('error', [$e, $this]);
                    }
                );
            }
        }

        $this->useEtf = false;

        $this->handlers = new Handlers();

        $loop->nextTick(
            function () use (&$loop) {
                $this->http->setDriver(new Guzzle($loop));
            }
        );

        $this->loop = $loop;

        $this->wsfactory->__invoke($this->gateway)->then(
            [$this, 'handleWebSocketConnection'],
            [$this, 'handleWebSocketError']
        );
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
        $ws->on(
            'message',
            function ($message, $ws) {
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

                if (isset($data->d->unavailable)) {
                    $this->emit('unavailable', [$data->t, $data->d]);

                    if ($data->t == Event::GUILD_DELETE) {
                        foreach ($this->discord->guilds as $index => $guild) {
                            if ($guild->id == $data->d->id) {
                                $this->discord->guilds->pull($index);
                                break;
                            }
                        }
                    }

                    return;
                }

                if (isset($data->s) && ! is_null($data->s)) {
                    $this->seq = $data->s;
                }

                if (! is_null($handlerSettings = $this->handlers->getHandler($data->t))) {
                    $this->handleHandler($handlerSettings, $data);
                }

                switch ($data->op) {
                    // Gateway Redirect
                    case 7:
                        $this->ws->close(1000, 'gateway redirecting - opcode 7');

                        break;
                    // Invalid Session
                    case 9:
                        $this->ws->close(4006, 'invalid session - opcode 9');

                        break;
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
        );

        $ws->on(
            'close',
            function ($op, $reason) {
                if ($op instanceof Stream) {
                    $op     = 1006;
                    $reason = 'PHP Stream closed.';
                }

                $this->emit('close', [$op, $reason, $this->discord]);

                if (! is_null($this->heartbeat)) {
                    $this->loop->cancelTimer($this->heartbeat);
                }

                if ($this->redirecting) {
                    $this->emit('redirecting', [$this->endpoint, $this]);
                    $this->redirecting  = false;
                    $this->reconnecting = true;
                    $this->wsfactory->__invoke($this->gateway)->then(
                        [$this, 'handleWebSocketConnection'],
                        [$this, 'handleWebSocketError']
                    );

                    return;
                }

                if ($this->reconnectCount >= 4) {
                    $this->emit('ws-reconnect-max', [$this->discord]);

                    return;
                }

                // Invalid Session
                if ($op == 4006 && strpos($reason, 'invalid session') !== false) {
                    $this->reconnecting = false;
                    $this->invalidSession = true;
                    $this->wsfactory->__invoke($this->gateway)->then(
                        [$this, 'handleWebSocketConnection'],
                        [$this, 'handleWebSocketError']
                    );
                    ++$this->reconnectCount;

                    return;
                }

                if (! $this->reconnecting) {
                    $this->emit('reconnecting', [$this->discord]);

                    $this->reconnecting = true;
                    $this->getGateway();
                    $this->wsfactory->__invoke($this->gateway)->then(
                        [$this, 'handleWebSocketConnection'],
                        [$this, 'handleWebSocketError']
                    );
                    ++$this->reconnectCount;
                }
            }
        );

        $ws->on(
            'error',
            function ($error, $ws) {
                $this->emit('error', [$error, $ws, $this->discord]);
            }
        );

        $this->ws = $ws;

        if ($this->reconnecting && ! is_null($this->sessionId)) {
            $this->send(
                [
                    'op' => 6,
                    'd'  => [
                        'token'      => $this->token,
                        'session_id' => $this->sessionId,
                        'seq'        => $this->seq,
                    ],
                ]
            );
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
        $this->emit('error', [$e, $this]);
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

        $this->reconnectResetTimer = $this->loop->addTimer(
            60 * 2,
            function () {
                $this->reconnectCount = 0;
            }
        );

        $tts = $data->d->heartbeat_interval / 1000;
        $this->heartbeat();
        $this->heartbeat = $this->loop->addPeriodicTimer($tts / 4, [$this, 'heartbeat']);

        // don't want to reparse ready
        if ($this->reconnecting) {
            $this->reconnecting = false;

            return;
        }

        $content = $data->d;

        // guilds
        $guilds = new GuildRepository(
            $this->http,
            $this->cache,
            $this->partFactory
        );

        foreach ($content->guilds as $guild) {
            $guildPart = $this->partFactory->create(Guild::class, $guild, true);

            $channels = new ChannelRepository(
                $this->http,
                $this->cache,
                $this->partFactory,
                ['guild_id' => $guildPart->id]
            );

            foreach ($guild->channels as $channel) {
                $channel             = (array) $channel;
                $channel['guild_id'] = $guild->id;
                $channelPart         = $this->partFactory->create(Channel::class, $channel, true);

                $channels->push($channelPart);

                $this->cache->set("channels.{$channelPart->id}", $channelPart);
            }

            $guildPart->channels = $channels;

            unset($channels);

            // guild members
            $members = new MemberRepository(
                $this->http,
                $this->cache,
                $this->partFactory,
                ['guild_id' => $guildPart->id]
            );

            foreach ($guild->members as $member) {
                $member             = (array) $member;
                $member['guild_id'] = $guild->id;
                $member['status']   = 'offline';
                $member['game']     = null;
                $memberPart         = $this->partFactory->create(Member::class, $member, true);

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
                // $this->cache->set("guild.{$memberPart->guild_id}.members.{$memberPart->id}", $memberPart);
            }

            $guildPart->members = $members;

            unset($members);

            // guild roles
            $roles = new RoleRepository(
                $this->http,
                $this->cache,
                $this->partFactory,
                ['guild_id' => $guildPart->id]
            );

            foreach ($guild->roles as $role) {
                $role                = (array) $role;
                $role['guild_id']    = $guild->id;
                $rolePart            = $this->partFactory->create(Role::class, $role, true);

                $roles->push($rolePart);

                $this->cache->set("roles.{$rolePart->id}", $rolePart);
            }

            $guildPart->roles = $roles;

            unset($roles);

            $guilds->push($guildPart);

            if ($guildPart->large) {
                $this->largeServers[$guildPart->id] = $guildPart;
            }

            $this->cache->set("guild.{$guildPart->id}", $guildPart);
        }

        $this->discord->guilds = $guilds;

        unset($guilds);

        $this->sessionId = $content->session_id;

        // guild_member_chunk
        if (count($this->largeServers) > 0) {
            $servers = [];
            foreach ($this->largeServers as $server) {
                $servers[] = $server->id;
            }

            $chunks = array_chunk($servers, 50);

            $sendChunk = function () use (&$sendChunk, &$chunks) {
                $chunk = array_pop($chunks);

                // We have finished our chunks
                if (is_null($chunk)) {
                    return;
                }

                $this->send(
                    [
                        'op' => 8,
                        'd'  => [
                            'guild_id' => $chunk,
                            'query'    => '',
                            'limit'    => 0,
                        ],
                    ]
                );

                $this->loop->addTimer(1, $sendChunk);
            };

            $sendChunk();

            unset($servers);
        } else {
            if (! $this->invalidSession) {
                $this->emit('ready', [$this->discord]);
            }

            $this->invalidSession = false;
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

        if (count($this->largeServers) === 0) {
            return;
        }

        foreach ($this->discord->guilds as $index => $guild) {
            if ($guild->id == $data->d->guild_id) {
                if (is_null($guild)) {
                    return;
                }

                foreach ($members as $member) {
                    if (isset($guild->members[$member->user->id])) {
                        continue;
                    }

                    $member             = (array) $member;
                    $member['guild_id'] = $data->d->guild_id;
                    $member['status']   = 'offline';
                    $member['game']     = null;
                    $memberPart         = $this->partFactory->create(Member::class, $member, true);

                    $guild->members[$memberPart->id] = $memberPart;
                }

                if ($guild->members->count() == $guild->member_count) {
                    unset($this->largeServers[$data->d->guild_id]);
                    $this->emit('guild-ready', [$guild]);
                }

                if (count($this->largeServers) === 0) {
                    $this->emit('ready', [$this->discord]);
                }

                break;
            }
        }

        unset($members);
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
        /** @var Event $handler */
        $handler = new $handlerSettings['class']($this->http, $this->partFactory, $this->cache, $this->discord);

        $deferred = new Deferred();
        $handler->handle($deferred, $data->d);

        $deferred->promise()->then(
            function ($handlerData) use ($data, $handlerSettings) {
                $this->emit($data->t, [$handlerData]);

                foreach ($handlerSettings['alternatives'] as $alternative) {
                    $this->emit($alternative, [$handlerData]);
                }

                if ($data->t == Event::MESSAGE_CREATE) {
                    $isMention = strpos($handlerData->content, '<@'.$this->discord->id.'>') !== false;

                    if ($isMention) {
                        $this->emit('mention', [$handlerData]);
                    }
                }
            }
        );
    }

    /**
     * Runs a heartbeat.
     *
     * @return void
     */
    public function heartbeat()
    {
        $time = microtime(true);

        $this->send(
            [
                'op' => 1,
                'd'  => $time,
            ]
        );

        $this->emit('heartbeat', [$time]);
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
                $vc->once(
                    'ready',
                    function () use ($vc, $deferred, $channel) {
                        $vc->setBitrate($channel->bitrate)->then(
                            function () use ($vc, $deferred) {
                                $deferred->resolve($vc);
                            }
                        );
                    }
                );
                $vc->once(
                    'error',
                    function ($e) use ($deferred) {
                        $deferred->reject($e);
                    }
                );
                $vc->once(
                    'close',
                    function () use ($channel) {
                        unset($this->voiceClients[$channel->guild_id]);
                    }
                );
                $this->voiceClients[$channel->guild_id] = $vc;

                $this->ws->removeListener('message', $closure);
            }
        };

        $this->ws->on('message', $closure);

        $this->send(
            [
                'op' => 4,
                'd'  => [
                    'guild_id'   => $channel->guild_id,
                    'channel_id' => $channel->id,
                    'self_mute'  => $mute,
                    'self_deaf'  => $deaf,
                ],
            ]
        );

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
        $token = (substr($this->token, 0, 4) === 'Bot ') ? substr($this->token, 4) : $this->token;

        $this->send(
            [
                'op' => 2,
                'd'  => [
                    'token'           => $token,
                    'v'               => 4,
                    'properties'      => [
                        '$os'               => PHP_OS,
                        '$browser'          => $this->http->getUserAgent(),
                        '$device'           => '',
                        '$referrer'         => 'https://github.com/teamreflex/DiscordPHP',
                        '$referring_domain' => 'https://github.com/teamreflex/DiscordPHP',
                    ],
                    'large_threshold' => 250,
                    'compress'        => true,
                ],
            ]
        );
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
     * @return void
     */
    public function getGateway()
    {
        $response = $this->http->get('gateway', null, null, null, true);

        // $this->gateway = $response->url;
        $this->gateway = 'wss://gateway.discord.gg/?v=4';
    }
}
