<?php

namespace Discord\WebSockets;

use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\WebSockets\Handlers;
use Evenement\EventEmitter;
use Ratchet\Client\Factory as WsFactory;
use Ratchet\Client\WebSocket as WebSocketInstance;
use Ratchet\WebSocket\Version\RFC6455\Frame;
use React\EventLoop\Factory as LoopFactory;

class WebSocket extends EventEmitter
{
    /**
     * The WebSocket event loop.
     *
     * @var React\EventLoop\Factory 
     */
    protected $loop;

    /**
     * The WebSocket factory.
     *
     * @var Ratchet\Client\Factory 
     */
    protected $wsfactory;

    /**
     * The WebSocket instance.
     *
     * @var WebSocketInstance 
     */
    protected $ws;

    /**
     * The Discord instance.
     *
     * @var Discord\Discord 
     */
    protected $discord;

    /**
     * The Discord WebSocket gateway.
     *
     * @var string
     */
    protected $gateway;

    /**
     * Have we sent the login frame yet?
     *
     * @var boolean 
     */
    protected $sentLoginFrame = false;

    /**
     * The event handlers.
     *
     * @var Handlers 
     */
    protected $handlers;

    /**
     * Constructs the WebSocket instance.
     *
     * @param Discord $discord 
     * @return void 
     */
    public function __construct(Discord $discord)
    {
        $this->discord = $discord;
        $this->gateway = $this->getGateway();

        $this->loop = LoopFactory::create();
        $this->wsfactory = new WsFactory($this->loop);

        $this->handlers = new Handlers();
    }

    /**
     * Runs the WebSocket client.
     *
     * @return void 
     */
    public function run()
    {
        $this->wsfactory->__invoke($this->gateway)->then(function (WebSocketInstance $ws) {
            $this->ws = $ws;

            $ws->on('message', function ($data, $ws) {
                $this->emit('raw', [$data, $this->discord]);
                $data = json_decode($data);

                if (!is_null($handler = $this->handlers->getHandler($data->t))) {
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
                            'd' => microtime(true) * 1000
                        ]);
                    });

                    $content = $data->d;

                    // set user settings obtain guild data etc.

                    // user client settings
                    $this->discord->user_settings = $content->user_settings;

                    // guilds
                    $guilds = new Collection();

                    foreach ($content->guilds as $guild) {
                        $guildPart = new Guild([
                            'id'                => $guild->id,
                            'name'              => $guild->name,
                            'icon'              => $guild->icon,
                            'region'            => $guild->region,
                            'owner_id'          => $guild->owner_id,
                            'roles'             => $guild->roles,
                            'joined_at'         => $guild->joined_at,
                            'afk_channel_id'    => $guild->afk_channel_id,
                            'afk_timeout'       => $guild->afk_timeout,
                            'large'             => $guild->large,
                            'features'          => $guild->features,
                            'splash'            => $guild->splash,
                            'emojis'            => $guild->emojis
                        ], true);

                        $channels = new Collection();

                        foreach ($guild->channels as $channel) {
                            $channelPart = new Channel([
                                'id'                    => $channel->id,
                                'name'                  => $channel->name,
                                'type'                  => $channel->type,
                                'topic'                 => $channel->topic,
                                'guild_id'              => $guild->id,
                                'position'              => $channel->position,
                                'last_message_id'       => $channel->last_message_id,
                                'permission_overwrites' => $channel->permission_overwrites
                            ], true);

                            $channels->push($channelPart);
                        }

                        $guildPart->setCache('channels', $channels);

                        // preload
                        $guildPart->getBansAttribute();

                        // guild members
                        $members = new Collection();

                        foreach ($guild->members as $member) {
                            $memberPart = new Member([
                                'user'      => $member->user,
                                'roles'     => $member->roles,
                                'mute'      => $member->mute,
                                'deaf'      => $member->deaf,
                                'joined_at' => $member->joined_at,
                                'guild_id'  => $guild->id,
                                'status'    => 'offline',
                                'game'      => null
                            ], true);

                            // check for presences

                            foreach ($guild->presences as $presence) {
                                if ($presence->user->id == $member->user->id) {
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

            if (!$this->sentLoginFrame) {
                $this->sendLoginFrame();
                $this->sentLoginFrame = true;
                $this->emit('sent-login-frame', [$ws, $this->discord]);
            }
        }, function ($e) {
            $this->emit('connectfail', [$e]);
            $this->loop->stop();
        });

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
                    '$referring_domain' => 'https://github.com/teamreflex/DiscordPHP/'
                ],
                'large_threshold' => 100
            ]
        ]);
    }

    /**
     * Sends data over the WebSocket.
     *
     * @param array $data 
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
     * @return string
     */
    public function getGateway()
    {
        return Guzzle::get('gateway')->url;
    }
}
