<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Voice;

use Discord\Exceptions\DCANotFoundException;
use Discord\Exceptions\FFmpegNotFoundException;
use Discord\Exceptions\FileNotFoundException;
use Discord\Parts\Channel\Channel;
use Discord\WSClient\Factory as WsFactory;
use Discord\WSClient\WebSocket as WS;
use Discord\WebSockets\WebSocket;
use Evenement\EventEmitter;
use Ratchet\WebSocket\Version\RFC6455\Frame;
use Discord\Helpers\Process;
use React\Datagram\Factory as DatagramFactory;
use React\Datagram\Socket;
use React\Dns\Resolver\Factory as DNSFactory;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Stream\Stream;

/**
 * The Discord voice client.
 */
class VoiceClient extends EventEmitter
{
    /**
     * Is the voice client ready?
     *
     * @var bool Whether the voice client is ready.
     */
    protected $ready = false;

    /**
     * The DCA binary name that we will use.
     *
     * @var string The DCA binary name that will be run.
     */
    protected $dca;

    /**
     * The ReactPHP event loop.
     *
     * @var LoopInterface The ReactPHP event loop that will run everything.
     */
    protected $loop;

    /**
     * The main WebSocket instance.
     *
     * @var WebSocket The main WebSocket client.
     */
    protected $mainWebsocket;

    /**
     * The voice WebSocket instance.
     *
     * @var WS The voice WebSocket client.
     */
    protected $voiceWebsocket;

    /**
     * The UDP client.
     *
     * @var Socket The voiceUDP client.
     */
    public $client;

    /**
     * The Channel that we are connecting to.
     *
     * @var Channel The channel that we are going to connect to.
     */
    protected $channel;

    /**
     * Data from the main WebSocket.
     *
     * @var array Information required for the voice WebSocket.
     */
    protected $data;

    /**
     * The Voice WebSocket endpoint.
     *
     * @var string The endpoint the Voice WebSocket and UDP client will connect to.
     */
    protected $endpoint;

    /**
     * The port the UDP client will use.
     *
     * @var int The port that the UDP client will connect to.
     */
    protected $udpPort;

    /**
     * The UDP heartbeat interval.
     *
     * @var int How often we send a heartbeat packet.
     */
    protected $heartbeat_interval;

    /**
     * The Voice WebSocket heartbeat timer.
     *
     * @var TimerInterface The heartbeat periodic timer.
     */
    protected $heartbeat;

    /**
     * The UDP heartbeat timer.
     *
     * @var TimerInterface The heartbeat periodic timer.
     */
    protected $udpHeartbeat;

    /**
     * The UDP heartbeat sequence.
     *
     * @var int The heartbeat sequence.
     */
    protected $heartbeatSeq = 0;

    /**
     * The SSRC value.
     *
     * @var int The SSRC value used for RTP.
     */
    public $ssrc;

    /**
     * The sequence of audio packets being sent.
     *
     * @var int The sequence of audio packets.
     */
    protected $seq = 0;

    /**
     * The timestamp of the last packet.
     *
     * @var int The timestamp the last packet was constructed.
     */
    protected $timestamp = 0;

    /**
     * The Voice WebSocket mode.
     *
     * @var string The voice mode.
     */
    protected $mode = 'plain';

    /**
     * Are we currently set as speaking?
     *
     * @var bool Whether we are speaking or not.
     */
    protected $speaking = false;

    /**
     * Whether we are set as mute.
     *
     * @var bool Whether we are set as mute.
     */
    protected $mute = false;

    /**
     * Whether we are set as deaf.
     *
     * @var bool Whether we are set as deaf.
     */
    protected $deaf = false;

    /**
     * Whether the voice client is currently paused.
     *
     * @var bool Whether the voice client is currently paused.
     */
    protected $isPaused = false;

    /**
     * Have we sent the login frame yet?
     *
     * @var bool Whether we have sent the login frame.
     */
    protected $sentLoginFrame = false;

    /**
     * The time we started sending packets.
     *
     * @var epoch The time we started sending packets.
     */
    protected $startTime;

    /**
     * The stream time of the last packet.
     *
     * @var int The time we sent the last packet.
     */
    protected $streamTime = 0;

    /**
     * The current time between voice packets.
     *
     * @var int The time between voice packets.
     */
    protected $betweenPackets;

    /**
     * Array of the status of people speaking.
     *
     * @var array Status of people speaking.
     */
    protected $speakingStatus = [];

    /**
     * The volume percentage the audio will be encoded with.
     *
     * @var int The volume percentage that the audio will be encoded in.
     */
    public $volume = 70;

    /**
     * The audio application to encode with.
     *
     * Available: voip, audio (default), lowdelay
     *
     * @var string The audio application.
     */
    public $audioApplication = 'audio';

    /**
     * The bitrate to encode with.
     *
     * @var int Encoding bitrate.
     */
    public $bitrate = 64000;

    /**
     * Constructs the Voice Client instance.
     *
     * @param WebSocket     $websocket The main WebSocket client.
     * @param LoopInterface $loop      The ReactPHP event loop.
     * @param Channel       $channel   The channel we are connecting to.
     * @param array         $data      More information related to the voice client.
     *
     * @return void
     */
    public function __construct(WebSocket $websocket, LoopInterface &$loop, Channel $channel, $data)
    {
        $this->mainWebsocket = $websocket;
        $this->channel = $channel;
        $this->data = $data;
        $this->deaf = $data['deaf'];
        $this->mute = $data['mute'];
        $this->endpoint = str_replace([':80', ':443'], '', $data['endpoint']);

        $this->checkForFFmpeg();
        $this->checkForDCA();

        $this->loop = $this->initSockets($loop);
    }

    /**
     * Initilizes the WebSocket and UDP socket.
     *
     * @return void
     */
    public function initSockets($loop)
    {
        $wsfac = new WsFactory($loop);
        $resolver = (new DNSFactory())->createCached('8.8.8.8', $loop);
        $udpfac = new DatagramFactory($loop, $resolver);

        $wsfac("wss://{$this->endpoint}")->then(function (WS $ws) use ($udpfac, &$loop) {
            $this->voiceWebsocket = $ws;

            $firstPack = true;
            $ip = $port = '';

            $discoverUdp = function ($message) use (&$ws, &$discoverUdp, $udpfac, &$firstPack, &$ip, &$port, &$loop) {
                $data = json_decode($message);

                if ($data->op == 2) {
                    $ws->removeListener('message', $discoverUdp);

                    $this->udpPort = $data->d->port;
                    $this->heartbeat_interval = $data->d->heartbeat_interval;
                    $this->ssrc = $data->d->ssrc;

                    $this->send([
                        'op' => 3,
                        'd' => microtime(true),
                    ]);
                    $this->emit('ws-heartbeat', []);

                    $this->heartbeat = $loop->addPeriodicTimer($this->heartbeat_interval / 1000, function () {
                        $this->send([
                            'op' => 3,
                            'd' => microtime(true),
                        ]);
                        $this->emit('ws-heartbeat', []);
                    });

                    $buffer = new Buffer(70);
                    $buffer->writeUInt32BE($this->ssrc, 3);

                    $udpfac->createClient("{$this->endpoint}:{$this->udpPort}")->then(function (Socket $client) use (&$ws, &$firstPack, &$ip, &$port, $buffer, &$loop) {
                        $this->client = $client;

                        $loop->addTimer(0.1, function () use (&$client, $buffer) {
                            $client->send((string) $buffer);
                        });

                        $this->udpHeartbeat = $loop->addPeriodicTimer(5, function () use ($client) {
                            $buffer = new Buffer(9);
                            $buffer[0] = pack('c', 0xC9);
                            $buffer->writeUInt64LE($this->heartbeatSeq, 1);
                            ++$this->heartbeatSeq;

                            $client->send((string) $buffer);
                            $this->emit('udp-heartbeat', []);
                        });

                        $client->on('error', function ($e) {
                            $this->emit('udp-error', [$e]);
                        });

                        $decodeUDP = function ($message) use (&$decodeUDP, $client, &$ip, &$port) {
                            $message = (string) $message;
                            // let's get our IP
                            $ip_start = 4;
                            $ip = substr($message, $ip_start);
                            $ip_end = strpos($ip, "\x00");
                            $ip = substr($ip, 0, $ip_end);

                            // now the port!
                            $port = substr($message, strlen($message) - 2);
                            $port = unpack('v', $port)[1];

                            $payload = [
                                'op' => 1,
                                'd' => [
                                    'protocol' => 'udp',
                                    'data' => [
                                        'address' => $ip,
                                        'port' => (int) $port,
                                        'mode' => $this->mode,
                                    ],
                                ],
                            ];

                            $this->send($payload);

                            $client->removeListener('message', $decodeUDP);
                        };

                        $client->on('message', $decodeUDP);
                        $client->on('message', function ($message) {
                            $this->emit('raw', [$message, $this]);
                        });
                    }, function ($e) {
                        $this->emit('error', [$e]);
                    });
                }
            };

            $ws->on('message', $discoverUdp);
            $ws->on('message', function ($message) {
                $data = json_decode($message);

                $this->emit('ws-message', [$message, $this]);

                switch ($data->op) {
                    case 3: // keepalive response
                        $end = microtime(true);
                        $start = $data->d;
                        $diff = (($end - $start) * 1000);
                        $timer = $diff / 2;

                        if ($timer > 20) {
                            $timer = 20;
                        } elseif ($timer < 16) {
                            $timer = 16;
                        }

                        $this->betweenPackets = $timer;

                        $this->emit('ws-ping', [$diff]);
                        break;
                    case 4: // ready
                        $this->ready = true;
                        $this->mode = $data->d->mode;
                        $this->emit('ready', [$this]);
                        break;
                    case 5: // user started speaking
                        $this->emit('speaking', [$data->d->speaking, $data->d->user_id, $this]);
                        $this->speakingStatus[$data->d->user_id] = $data->d;
                        break;
                }
            });

            $ws->on('error', function ($e) {
                $this->emit('ws-error', [$e]);
            });

            if (! $this->sentLoginFrame) {
                $this->send([
                    'op' => 0,
                    'd' => [
                        'server_id' => $this->channel->guild_id,
                        'user_id' => $this->data['user_id'],
                        'session_id' => $this->data['session'],
                        'token' => $this->data['token'],
                    ],
                ]);
                $this->sentLoginFrame = true;
            }
        }, function ($e) {
            $this->emit('error', [$e]);
        });

        return $loop;
    }

    /**
     * Plays a file on the voice stream.
     *
     * @param string $file     The file to play.
     * @param int    $channels How many audio channels to encode with.
     *
     * @return \React\Promise\Promise
     *
     * @throws FileNotFoundException Thrown when the file specified could not be found.
     */
    public function playFile($file, $channels = 2)
    {
        $deferred = new Deferred();

        if (! file_exists($file)) {
            $deferred->reject(new FileNotFoundException("Could not find the file \"{$file}\"."));

            return $deferred->promise();
        }

        if (! $this->ready) {
            $deferred->reject(new \Exception('Voice Client is not ready.'));

            return $deferred->promise();
        }

        $process = $this->dcaConvert($file);
        $process->start($this->loop);

        $this->playDCAStream($process)->then(function ($result) use ($deferred) {
            $deferred->resolve($result);
        });

        return $deferred->promise();
    }

    /**
     * Plays a PHP resource stream.
     *
     * @param resource|Stream $stream   The stream to be encoded and sent.
     * @param int             $channels How many audio channels to encode with.
     *
     * @return \React\Promise\Promise
     *
     * @throws \RuntimeException Thrown when the stream passed to playRawStream is not a valid resource.
     */
    public function playRawStream($stream, $channels = 2)
    {
        $deferred = new Deferred();

        if (! $this->ready) {
            $deferred->reject(new \Exception('Voice Client is not ready.'));

            return $deferred->promise();
        }

        if (! is_resource($stream) && !$stream instanceof Stream) {
            $deferred->reject(new \RuntimeException('The stream passed to playRawStream was not an instance of resource or ReactPHP Stream.'));

            return $deferred->promise();
        }

        if (is_resource($stream)) {
            $stream = new Stream($stream, $this->loop);
        }

        $process = $this->dcaConvert();
        $process->start($this->loop);

        $stream->pipe($process->stdin);

        $this->playDCAStream($process)->then(function ($result) use ($deferred, $stream) {
            $stream->close();
            $deferred->resolve($result);
        }, function ($e) use ($deferred, $stream) {
            $stream->close();
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * Plays a DCA stream.
     *
     * @param resource|Process|Stream $stream The DCA stream to be sent.
     *
     * @return \React\Promise\Promise
     */
    public function playDCAStream($stream)
    {
        $deferred = new Deferred();
        $process;

        if (! $this->ready) {
            $deferred->reject(new \Exception('Voice Client is not ready.'));

            return $deferred->promise();
        }

        if ($stream instanceof Process) {
            $process = $stream;
            $process->stderr->on('data', function ($d) {
                if (empty($d)) {
                    return;
                }

                $this->emit('stderr', [$d, $this]);
            });

            $stream = $stream->stdout;
        }

        if ($stream instanceof Stream) {
            $stream->pause();
            $stream = $stream->stream;
        }

        if (! is_resource($stream)) {
            $deferred->reject(new \RuntimeException('The stream passed to playDCAStream was not an instance of resource, ReactPHP Process or ReactPHP Stream.'));

            return $deferred->promise();
        }

        $count = 0;
        $noData = false;
        $noDataHeader = false;

        $this->setSpeaking(true);

        $processff2opus = function () use (&$processff2opus, $stream, &$noData, &$noDataHeader, $deferred, &$count, $process) {
            $length = $this->betweenPackets;

            if (empty($length)) {
                $length = 17.47;
            }

            if ($this->isPaused) {
                $this->loop->addTimer($length / 1000, $processff2opus);

                return;
            }

            if ($length >= 20) {
                $length = 17.47;
            } elseif ($length <= 17) {
                $length = 17.47;
            }

            $header = @fread($stream, 2);

            if (! $header) {
                if ($noDataHeader && $this->streamTime != 0) {
                    $this->setSpeaking(false);

                    $this->seq = 0;
                    $this->timestamp = 0;
                    $this->streamTime = 0;
                    $this->startTime = null;

                    if (isset($process)) {
                        $process->close();
                    }

                    $deferred->resolve(true);
                } else {
                    $noDataHeader = true;
                    $this->loop->addTimer($length / 1000, $processff2opus);
                }

                return;
            }

            $opusLength = unpack('v', $header);
            $opusLength = reset($opusLength);
            $buffer = fread($stream, $opusLength);

            if (! $this->speaking) {
                $this->setSpeaking(true);
            }

            if (strlen($buffer) !== $opusLength) {
                $newbuff = new Buffer($opusLength);
                $newbuff->write($buffer, 0);
                $buffer = (string) $newbuff;
            }

            ++$count;

            $this->sendBuffer($buffer);

            if (($this->seq + 1) < 65535) {
                ++$this->seq;
            } else {
                $this->seq = 0;
            }

            if (($this->timestamp + 960) < 4294967295) {
                $this->timestamp += 960;
            } else {
                $this->timestamp = 0;
            }

            $next = $this->startTime + ($count * $length);
            $this->streamTime = $count * $length;

            // There is a delay so it isn't exactly 20ms after the last packet, it is about 17.47ms (i think)
            $this->loop->addTimer($length / 1000, $processff2opus);
        };

        $processff2opus();

        return $deferred->promise();
    }

    /**
     * Sends a buffer to the UDP socket.
     *
     * @param string $data     The data to send to the UDP server.
     * @param int    $channels How many audio channels to encode with.
     *
     * @return void
     */
    public function sendBuffer($data)
    {
        if (! $this->ready) {
            return;
        }

        $packet = new VoicePacket($data, $this->ssrc, $this->seq, $this->timestamp);
        $this->client->send((string) $packet);

        $this->streamTime = microtime(true);

        $this->emit('packet-sent', [$packet]);
    }

    /**
     * Sets the speaking value of the client.
     *
     * @param bool $speaking Whether the client is speaking or not.
     *
     * @return bool Whether the client is speaking or not.
     */
    public function setSpeaking($speaking = true)
    {
        $deferred = new Deferred();

        if ($this->speaking == $speaking) {
            $deferred->resolve();

            return $deferred->promise();
        }

        if (! $this->ready) {
            $deferred->reject(new \Exception('Voice Client is not ready.'));

            return $deferred->promise();
        }

        $this->send([
            'op' => 5,
            'd' => [
                'speaking' => $speaking,
                'delay' => 0,
            ],
        ]);

        $this->speaking = $speaking;

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Switches voice channels.
     *
     * @param Channel $channel The channel to switch to.
     *
     * @return \React\Promise\Promise
     */
    public function switchChannel(Channel $channel)
    {
        $deferred = new Deferred();

        if ($channel->type != Channel::TYPE_VOICE) {
            $deferred->reject(new \InvalidArgumentException('Channel must be a voice chnanel to be able to switch'));

            return $deferred->promise();   
        }

        $this->mainWebsocket->send([
            'op' => 4,
            'd' => [
                'guild_id' => $channel->guild_id,
                'channel_id' => $channel->id,
                'self_mute' => $this->mute,
                'self_deaf' => $this->deaf
            ]
        ]);

        $this->channel = $channel;

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Sends a message to the voice websocket.
     *
     * @param array $data The data to send to the voice WebSocket.
     *
     * @return void
     */
    public function send(array $data)
    {
        $frame = new Frame(json_encode($data), true);
        $this->voiceWebsocket->send($frame);
    }

    /**
     * Changes your mute and deaf value.
     *
     * @param bool $mute Whether you should be muted.
     * @param bool $deaf Whether you should be deaf.
     *
     * @return void
     */
    public function setMuteDeaf($mute, $deaf)
    {
        $deferred = new Deferred();

        if (! $this->ready) {
            $deferred->reject(new \Exception('The voice client must be ready before you can set mute or deaf.'));

            return $deferred->promise();
        }

        $this->mute = $mute;
        $this->deaf = $deaf;

        $this->mainWebsocket->send([
            'op' => 4,
            'd' => [
                'guild_id' => $this->channel->guild_id,
                'channel_id' => $this->channel->id,
                'self_mute' => $mute,
                'self_deaf' => $deaf,
            ],
        ]);

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Pauses the current sound.
     *
     * @return \React\Promise\Promise 
     */
    public function pause()
    {
        $deferred = new Deferred();

        $this->isPaused = true;
        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Unpauses the current sound.
     *
     * @return \React\Promise\Promise 
     */
    public function unpause()
    {
        $deferred = new Deferred();

        $this->isPaused = false;
        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Leaves the voice channel.
     *
     * @return \React\Promise\Promise
     */
    public function leave()
    {
        $deferred = new Deferred();

        if (!$this->ready) {
            $deferred->reject(new \Exception('Voice Client is not connected.'));

            return $deferred->promise();
        }

        $this->setSpeaking(false);
        $this->ready = false;

        $this->mainWebsocket->send([
            'op' => 4,
            'd' => [
                'guild_id' => null,
                'channel_id' => null,
                'self_mute' => false,
                'self_deaf' => false,
            ],
        ]);

        $this->voiceWebsocket->close();
        $this->client->close();

        $this->heartbeat_interval = null;
        $this->loop->cancelTimer($this->heartbeat);
        $this->loop->cancelTimer($this->udpHeartbeat);
        $this->heartbeat = null;
        $this->udpHeartbeat = null;
        $this->seq = 0;
        $this->timestamp = 0;
        $this->sentLoginFrame = false;
        $this->startTime = null;
        $this->streamTime = 0;
        $this->speakingStatus = [];

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Returns whether the voice client is ready.
     *
     * @return bool Whether the voice client is ready.
     */
    public function isReady()
    {
        return $this->ready;
    }

    /**
     * Checks if FFmpeg is installed.
     *
     * @return bool Whether FFmpeg is installed or not.
     *
     * @throws \Discord\Exceptions\FFmpegNotFoundException Thrown when FFmpeg is not found.
     */
    public function checkForFFmpeg()
    {
        $binaries = [
            'ffmpeg',
        ];

        foreach ($binaries as $binary) {
            $output = shell_exec("which {$binary}");

            if (! empty($output)) {
                return;
            }
        }

        throw new FFmpegNotFoundException('No FFmpeg binary was found.');
    }

    /**
     * Checks if DCA is installed.
     *
     * @return bool Whether DCA is installed or not.
     *
     * @throws \Discord\Exceptions\DCANotFoundException Thrown when DCA is not found.
     */
    public function checkForDCA()
    {
        $binaries = [
            'dca',
            'ff2opus',
        ];

        foreach ($binaries as $binary) {
            $output = shell_exec("which {$binary}");

            if (! empty($output)) {
                $this->dca = $binary;

                return;
            }
        }

        throw new DCANotFoundException('No DCA binary was found.');
    }

    /**
     * Converts a file with DCA.
     *
     * @param string $filename The file name that will be converted
     * @param int    $channels How many audio channels to encode with.
     *
     * @return Process A ReactPHP Child Process
     */
    public function dcaConvert($filename = '', $channels = 2)
    {
        if (! empty($filename) && ! file_exists($filename)) {
            return;
        }

        $flags = [
            '-ac', $channels, // Channels
            '-aa', $this->audioApplication, // Audio application
            '-ab', $this->bitrate / 1000, // Bitrate
            '-i', (empty($filename)) ? 'pipe:0' : "\"{$filename}\"", // Input file
        ];

        $flags = implode(' ', $flags);

        return new Process("{$this->dca} {$flags}");
    }
}
