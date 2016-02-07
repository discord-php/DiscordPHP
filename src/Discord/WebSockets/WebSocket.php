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
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\Voice\VoiceClient;
use Discord\WSClient\Factory as WsFactory;
use Discord\WSClient\WebSocket as WebSocketInstance;
use Evenement\EventEmitter;
use Ratchet\WebSocket\Version\RFC6455\Frame;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

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
     * The Discord WebSocket gateway.
     *
     * @var string The Discord WebSocket gateway.
     */
    protected $gateway;

    /**
     * Have we sent the login frame yet?
     *
     * @var bool Whether we have sent the login frame.
     */
    protected $sentLoginFrame = false;

    /**
     * The event handlers.
     *
     * @var Handlers The Handlers class.
     */
    protected $handlers;

    /**
     * The Voice Client instance.
     *
     * @var VoiceClient The Voice Client.
     */
    protected $voice;

    /**
     * Constructs the WebSocket instance.
     *
     * @param Discord            $discord The Discord REST client instance.
     * @param LoopInterface|null $loop    The ReactPHP Event Loop.
     *
     * @return void
     */
    public function __construct(Discord $discord, LoopInterface &$loop = null)
    {
        $this->discord = $discord;
        $this->gateway = $this->getGateway();

        $loop = (is_null($loop)) ? LoopFactory::create() : $loop;

        $this->handlers = new Handlers();

        $this->loop = $this->setupWs($loop);
    }

    /**
     * Sets up the WebSocket.
     *
     * @param LoopInterface $loop The ReactPHP Event Loop.
     *
     * @return LoopInterface The ReactPHP Event Loop.
     */
    protected function setupWs(LoopInterface $loop)
    {
        $wsfactory = new WsFactory($loop);

        $wsfactory($this->gateway)->then(function (WebSocketInstance $ws) {
            $this->ws = $ws;

            $ws->on('message', function ($data, $ws) {
                $this->emit('raw', [$data, $this->discord]);
                $data = json_decode($data);

                if (isset($data->d->unavailable)) {
                    $this->emit('unavailable', [$data->t, $data->d]);

                    if ($data->t == Event::GUILD_DELETE) {
                        $discord = $this->discord;

                        foreach ($discord->guilds as $index => $guild) {
                            if ($guild->id == $data->d->id) {
                                $discord->guilds->pull($index);
                                break;
                            }
                        }

                        $this->discord = $discord;
                    }

                    return;
                }

                if (! is_null($handler = $this->handlers->getHandler($data->t))) {
                    $handler = new $handler();
                    $handlerData = $handler->getData($data->d, $this->discord);
                    $newDiscord = $handler->updateDiscordInstance($handlerData, $this->discord);
                    $this->emit($data->t, [$handlerData, $this->discord, $newDiscord]);
                    $this->discord = $newDiscord;
                }

                if ($data->t == Event::READY) {
                    $tts = $data->d->heartbeat_interval / 1000;
                    $this->loop->addPeriodicTimer($tts, function () use ($ws) {
                        $this->send([
                            'op' => 1,
                            'd' => microtime(true) * 1000,
                        ]);
                    });

                    $content = $data->d;

                    // set user settings obtain guild data etc.

                    // user client settings
                    $this->discord->user_settings = $content->user_settings;

                    // guilds
                    $guilds = new Collection();

                    foreach ($content->guilds as $guild) {
                        $guildPart = new Guild((array) $guild, true);

                        $channels = new Collection();

                        foreach ($guild->channels as $channel) {
                            $channel = (array) $channel;
                            $channel['guild_id'] = $guild->id;
                            $channelPart = new Channel($channel, true);

                            $channels->push($channelPart);
                        }

                        $guildPart->setCache('channels', $channels);

                        // preload
                        $guildPart->getBansAttribute();

                        // guild members
                        $members = new Collection();

                        foreach ($guild->members as $member) {
                            $member = (array) $member;
                            $member['guild_id'] = $guild->id;
                            $member['status'] = 'offline';
                            $member['game'] = null;
                            $memberPart = new Member($member, true);

                            // check for presences

                            foreach ($guild->presences as $presence) {
                                if ($presence->user->id == $member['user']->id) {
                                    $memberPart->status = $presence->status;
                                    $memberPart->game = $presence->game;
                                }
                            }

                            $members->push($memberPart);
                        }

                        $guildPart->setCache('members', $members);

                        $guilds->push($guildPart);
                    }

                    $this->discord->setCache('guilds', $guilds);

                    // after we do everything, emit ready
                    $this->emit('ready', [$this->discord]);
                }
            });

            $ws->on('close', function ($ws) {
                $this->emit('close', [$ws, $this->discord]);
            });

            $ws->on('error', function ($error, $ws) {
                $this->emit('error', [$error, $ws, $this->discord]);
            });

            if (! $this->sentLoginFrame) {
                $this->sendLoginFrame();
                $this->sentLoginFrame = true;
                $this->emit('sent-login-frame', [$ws, $this->discord]);
            }
        }, function ($e) {
            $this->emit('connectfail', [$e]);
            $this->loop->stop();
        });

        return $loop;
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
        if (! is_null($this->voice)) {
            return; //temp
        }

        $deferred = new Deferred();
        $arr = ['user_id' => $this->discord->id];

        $closure = function ($message) use (&$closure, &$arr, $deferred, $channel) {
            $data = json_decode($message);

            if ($data->t == 'VOICE_STATE_UPDATE') {
                $arr['session'] = $data->d->session_id;
            } elseif ($data->t == 'VOICE_SERVER_UPDATE') {
                $arr['token'] = $data->d->token;
                $arr['endpoint'] = $data->d->endpoint;

                $vc = new VoiceClient($this, $this->loop, $channel, $arr);
                $vc->once('ready', function () use ($vc, $deferred) {
                    $deferred->resolve($vc);
                });
                $vc->once('error', function ($e) use ($deferred) {
                    $deferred->reject($e);
                });
                $this->voice = $vc;

                $this->ws->removeListener('message', $closure);
            }
        };

        $this->ws->on('message', $closure);

        $this->send([
            'op' => 4,
            'd' => [
                'guild_id' => $channel->guild_id,
                'channel_id' => $channel->id,
                'self_mute' => $mute,
                'self_deaf' => $deaf,
            ],
        ]);

        return $deferred->promise();
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
        $this->send([
            'op' => 2,
            'd' => [
                'token' => DISCORD_TOKEN,
                'v' => 3,
                'properties' => [
                    '$os' => PHP_OS,
                    '$browser' => Guzzle::getUserAgent(),
                    '$device' => '',
                    '$referrer' => 'https://github.com/teamreflex/DiscordPHP',
                    '$referring_domain' => 'https://github.com/teamreflex/DiscordPHP/',
                ],
                'large_threshold' => 100,
                'compress' => true,
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
        $frame = new Frame(json_encode($data), true);
        $this->ws->send($frame);
    }

    /**
     * Gets the WebSocket gateway.
     *
     * @return string The Discord WebSocket gateway.
     */
    public function getGateway()
    {
        return Guzzle::get('gateway')->url;
    }
}
