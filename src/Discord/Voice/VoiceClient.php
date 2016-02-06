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

use Discord\Exceptions\FFmpegNotFoundException;
use Discord\Exceptions\FileNotFoundException;
use Discord\Exceptions\FormatNotSupportedException;
use Discord\Exceptions\OpusNotFoundException;
use Discord\Parts\Channel\Channel;
use Discord\WSClient\Factory as WsFactory;
use Discord\WSClient\WebSocket as WS;
use Discord\WebSockets\WebSocket;
use Evenement\EventEmitter;
use Ratchet\WebSocket\Version\RFC6455\Frame;
use React\ChildProcess\Process;
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
     * The FFmpeg binary name that we will use.
     *
     * @var string The FFmpeg binary name that will be run.
     */
    protected $binary;

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
    protected $client;

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
     * The SSRC value.
     *
     * @var int The SSRC value used for RTP.
     */
    protected $ssrc;

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
     * The volume percentage the audio will be encoded with.
     *
     * @var int The volume percentage that the audio will be encoded in.
     */
    public $volume = 70;

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
        $this->endpoint = str_replace([':80', ':443'], '', $data['endpoint']);

        $this->checkForFFmpeg();
        $this->checkForOpus();

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

                    $loop->addPeriodicTimer($this->heartbeat_interval / 1000, function () {
                        $this->send([
                            'op' => 3,
                            'd' => null,
                        ]);
                    });

                    $buffer = new Buffer(70);
                    $buffer->writeUInt32BE($this->ssrc, 3);

                    $udpfac->createClient("{$this->endpoint}:{$this->udpPort}")->then(function (Socket $client) use (&$ws, &$firstPack, &$ip, &$port, $buffer, &$loop) {
                        $this->client = $client;

                        $loop->addTimer(0.1, function () use (&$client, $buffer) {
                            $client->send((string) $buffer);
                        });

                        $client->on('error', function ($e) {
                            $this->emit('error', [$e]);
                        });

                        $client->on('message', function ($message) use (&$ws, &$firstPack, &$ip, &$port) {
                            dump($message);
                            if ($firstPack) {
                                $message = (string) $message;
                                // let's get our IP
                                $ip_start = 4;
                                $ip = substr($message, $ip_start);
                                $ip_end = strpos($ip, "\x00");
                                $ip = substr($ip, 0, $ip_end);

                                // now the port!
                                $port = substr($message, strlen($message) - 2);
                                $port = unpack('v', $port)[1];

                                dump($ip);
                                dump($port);

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

                                $firstPack = false;
                            }
                        });
                    }, function ($e) {
                        $this->emit('error', [$e]);
                    });
                }
            };

            $ws->on('message', $discoverUdp);
            $ws->on('message', function ($message) {
                $data = json_decode($message);

                dump($data);

                switch ($data->op) {
                    case 4: // ready
                        $this->ready = true;
                        $this->mode = $data->d->mode;
                        $this->emit('ready', [$this]);
                        break;
                }
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
     * @throws FileNotFoundException       Thrown when the file specified could not be found.
     * @throws FormatNotSupportedException Thrown when the file format is not supported.
     */
    public function playFile($file, $channels = 2)
    {
        $deferred = new Deferred();

        if (! file_exists($file)) {
            $deferred->reject(new FileNotFoundException("Could not find the file \"{$file}\"."));

            return $deferred->promise();
        }

        $format = explode('.', $file);

        if (isset($format[1])) {
            // Checks the file format
            unset($format[0]);
            $format = implode('.', $format);
            if (false === strpos($this->ffExec('-formats', false), $format[1])) {
                $deferred->reject(new FormatNotSupportedException('The format '.$format[1].' is not supported.'));

                return $deferred->promise();
            }
        }

        $this->playRawStream(fopen($file, 'r'))->then(function ($result) use ($deferred) {
            $deferred->resolve($result);
        }, function ($e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * Plays a PHP resource stream.
     *
     * @param resource $stream   The stream to be encoded and sent.
     * @param int      $channels How many audio channels to encode with.
     *
     * @return \React\Promise\Promise
     *
     * @throws \RuntimeException Thrown when the stream passed to playRawStream is not a valid resource.
     */
    public function playRawStream($stream, $channels = 2)
    {
        $deferred = new Deferred();

        if (! is_resource($stream)) {
            $deferred->reject(new \RuntimeException('The stream passed to playRawStream was not an instance of resource.'));

            return $deferred->promise();
        }

        $count = 0;
        $length = 20;
        $noData = false;

        $input = new Stream($stream, $this->loop);
        $memallowance = 20 * 1024 * 1024; // 20mb
        $output = fopen("php://temp/maxmemory:{$memallowance}", 'r+');

        $convert = function () use (&$input, &$output, $channels) {
            $deferred = new Deferred();

            $process = $this->ffExec([
                '-i', '-', // The input file (pipe in our instance)
                '-f', 'opus', // Output codec
                '-ar', 48000, // 48kb bitrate
                '-ac', $channels, // 2 Channels
                '-af', 'volume='.($this->volume / 100), // Volume
                'pipe:1', // Pipe to stdout
            ]);

            $process->start($this->loop);
            $input->pipe($process->stdin);
            $process->stdout->on('data', function ($data) use ($output) {
                fwrite($output, $data);
            });

            $process->on('exit', function () use ($deferred, &$input, &$output) {
                $input->close();

                rewind($output);
                $deferred->resolve($output);
            });

            return $deferred->promise();
        };

        $handleData = function ($stream) use ($channels, &$handleData, &$count, &$noData,  $length, $deferred) {
            $buffer = fread($stream, 1920 * $channels);

            if (! $buffer) {
                if ($noData) {
                    $this->setSpeaking(false);
                    $deferred->resolve();
                } else {
                    $noData = true;
                    $this->loop->addTimer($length / 100, function () use (&$handleData, &$stream) {
                        $handleData($stream);
                    });
                }

                return;
            }

            if (! $this->speaking) {
                $this->setSpeaking(true);
            }

            if (strlen($buffer) !== 1920 * $channels) {
                dump(strlen($buffer));
                $newbuff = new Buffer(1920 * $channels);
                $newbuff->write($buffer, 0);
                $buffer = (string) $newbuff;
            }

            ++$count;

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

            $this->sendBuffer($buffer);

            $next = $this->startTime + ($count * $length);
            $this->streamTime = $count * $length;
            dump($this->streamTime);

            $this->loop->addTimer(($length + ($next - microtime(true))) / 1000, function () use (&$handleData, &$stream) {
                $handleData($stream);
            });
        };

        $convert()->then(function ($stream) use ($handleData) {
            $this->setSpeaking(true);
            $this->startTime = microtime(true);

            $handleData($stream);
        });

        return $deferred->promise();
    }

    /**
     * Sends a buffer to the UDP socket.
     *
     * @param string $data The data to send to the UDP server.
     *
     * @return void
     */
    public function sendBuffer($data)
    {
        dump("ssrc: {$this->ssrc}, seq: {$this->seq}, timestamp: {$this->timestamp}");
        $packet = new VoicePacket($data, $this->ssrc, $this->seq, $this->timestamp);
        dump(strlen((string) $packet));
        $this->client->send((string) $packet);
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
        if ($this->speaking == $speaking) {
            return $speaking;
        }

        $this->send([
            'op' => 5,
            'd' => [
                'speaking' => $speaking,
                'delay' => 0,
            ],
        ]);

        $this->speaking = $speaking;

        return $speaking;
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
                $this->binary = $binary;

                return;
            }
        }

        throw new FFmpegNotFoundException('No FFmpeg binary was found.');
    }

    /**
     * Checks if FFmpeg was compiled with libopus enabled.
     *
     * @return void
     *
     * @throws \Discord\Exceptions\OpusNotFoundException Thrown when FFmpeg is not compiled with libopus enabled.
     */
    public function checkForOpus()
    {
        $output = $this->ffExec('-encoders', false);

        if (false === strpos($output, 'libopus')) {
            throw new OpusNotFoundException('FFmpeg was not compiled with Opus.');
        }
    }

    /**
     * Executes parameters on the FFmpeg binary.
     *
     * @param string|array $parameters The parameters to pass onto the FFmpeg binary.
     * @param bool         $advanced   Whether we should return a process or string.
     *
     * @return Process|string Either a ReactPHP Child Process or a string return.
     */
    public function ffExec($parameters, $advanced = true)
    {
        $command = "{$this->binary} -loglevel 0 ";

        foreach ((array) $parameters as $param) {
            $command .= "{$param} ";
        }

        if (! $advanced) {
            return shell_exec(trim($command));
        }

        return new Process(trim($command));
    }
}
