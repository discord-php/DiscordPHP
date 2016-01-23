<?php

namespace Discord\WebSockets;

use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Helpers\Guzzle;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\WSClient\Factory as WsFactory;
use Discord\WSClient\WebSocket as WebSocketInstance;
use Discord\WebSockets\Handlers;
use Evenement\EventEmitter;
use Ratchet\WebSocket\Version\RFC6455\Frame;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;

class WebSocket extends EventEmitter
{
    /**
     * The WebSocket event loop.
     *
     * @var React\EventLoop\Factory 
     */
    public $loop;

    /**
     * The WebSocket factory.
     *
     * @var WsFactory
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
     * @param LoopInterface $loop 
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
     * @param LoopInterface $loop 
     * @return LoopInterface
     */
    public function setupWs(LoopInterface $loop)
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

        return $loop;
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
                    '$referring_domain' => 'https://github.com/teamreflex/DiscordPHP/'
                ],
                'large_threshold' => 100,
                'compress' => true
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
