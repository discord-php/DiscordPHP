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
use Discord\Exceptions\LibSodiumNotFoundException;
use Discord\Exceptions\OutdatedDCAException;
use Discord\Helpers\Collection;
use Discord\Helpers\Process;
use Discord\Wrapper\LoggerWrapper as Logger;
use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Op;
use Evenement\EventEmitter;
use Ratchet\Client\Connector as WsFactory;
use Ratchet\Client\WebSocket;
use Ratchet\WebSocket\Version\RFC6455\Frame;
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
     * The DCA version the client is using.
     *
     * @var string The DCA version.
     */
    const DCA_VERSION = 'DCA1';

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
     * @var WebSocket The voice WebSocket client.
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
    protected $mode = 'xsalsa20_poly1305';

    /**
     * The secret key used for encrypting voice.
     *
     * @var string The secret key.
     */
    protected $secret_key;

    /**
     * Are we currently set as speaking?
     *
     * @var bool Whether we are speaking or not.
     */
    protected $speaking = false;

    /**
     * Should we stop the current playing audio?
     *
     * @var bool Whether we should stop the current playing audio.
     */
    protected $stopAudio = false;

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
     * The size of audio frames.
     *
     * @var int The size of audio frames.
     */
    protected $frameSize = 20;

    /**
     * Collection of the status of people speaking.
     *
     * @var Collection Status of people speaking.
     */
    protected $speakingStatus;

    /**
     * Collection of voice decoders.
     *
     * @var Collection Voice decoders.
     */
    protected $voiceDecoders;

    /**
     * Voice audio recieve streams.
     *
     * @var array Voice audio recieve streams.
     */
    protected $recieveStreams;

    /**
     * The volume the audio will be encoded with.
     *
     * @var int The volume that the audio will be encoded in.
     */
    protected $volume = 100;

    /**
     * The audio application to encode with.
     *
     * Available: voip, audio (default), lowdelay
     *
     * @var string The audio application.
     */
    protected $audioApplication = 'audio';

    /**
     * The bitrate to encode with.
     *
     * @var int Encoding bitrate.
     */
    protected $bitrate = 64000;

    /**
     * Is the voice client reconnecting?
     *
     * @var bool Whether the voice client is reconnecting.
     */
    protected $reconnecting = false;

    /**
     * The logger.
     *
     * @var Logger Logger.
     */
    protected $logger;

    /**
     * Constructs the Voice Client instance.
     *
     * @param WebSocket     $websocket The main WebSocket client.
     * @param LoopInterface $loop      The ReactPHP event loop.
     * @param Channel       $channel   The channel we are connecting to.
     * @param Logger        $logger    The logger.
     * @param array         $data      More information related to the voice client.
     *
     * @return void
     */
    public function __construct(WebSocket $websocket, LoopInterface &$loop, Channel $channel, Logger $logger, $data)
    {
        $this->loop           = $loop;
        $this->mainWebsocket  = $websocket;
        $this->channel        = $channel;
        $this->logger         = $logger;
        $this->data           = $data;
        $this->deaf           = $data['deaf'];
        $this->mute           = $data['mute'];
        $this->endpoint       = str_replace([':80', ':443'], '', $data['endpoint']);
        $this->speakingStatus = new Collection([], 'ssrc');

        $this->checkForFFmpeg();
        $this->checkForDCA();
        $this->checkForLibsodium();

        // temp
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->emit('error', [new \Exception('The voice client does not work on Windows operating systems at the moment.')]);

            return;
        }

        $this->initSockets();
    }

    /**
     * Initilizes the WebSocket and UDP socket.
     *
     * @return void
     */
    public function initSockets()
    {
        $wsfac = new WsFactory($this->loop);

        $wsfac("wss://{$this->endpoint}")->then(
            [$this, 'handleWebSocketConnection'],
            [$this, 'handleWebSocketError']
        );
    }

    /**
     * Handles a WebSocket connection.
     *
     * @param WebSocket $ws The WebSocket instance.
     *
     * @return void
     */
    public function handleWebSocketConnection(WebSocket $ws)
    {
        $this->logger->debug('connected to voice websocket');

        $resolver = (new DNSFactory())->createCached('8.8.8.8', $this->loop);
        $udpfac   = new DatagramFactory($this->loop, $resolver);

        $this->voiceWebsocket = $ws;

        $firstPack = true;
        $ip        = $port        = '';

        $discoverUdp = function ($message) use (&$ws, &$discoverUdp, $udpfac, &$firstPack, &$ip, &$port) {
            $data = json_decode($message->getPayload());

            if ($data->op == Op::VOICE_READY) {
                $ws->removeListener('message', $discoverUdp);

                $this->udpPort            = $data->d->port;
                $this->heartbeat_interval = $data->d->heartbeat_interval;
                $this->ssrc               = $data->d->ssrc;

                $this->logger->debug('received voice ready packet', ['data' => json_decode(json_encode($data->d), true)]);

                $this->send([
                    'op' => Op::VOICE_HEARTBEAT,
                    'd'  => microtime(true),
                ]);
                $this->emit('ws-heartbeat', []);

                $this->heartbeat = $this->loop->addPeriodicTimer($this->heartbeat_interval / 1000, function () {
                    $this->send([
                        'op' => Op::VOICE_HEARTBEAT,
                        'd'  => microtime(true),
                    ]);
                    $this->emit('ws-heartbeat', []);
                });

                $buffer = new Buffer(70);
                $buffer->writeUInt32BE($this->ssrc, 3);

                $udpfac->createClient("{$this->endpoint}:{$this->udpPort}")->then(function (Socket $client) use (&$ws, &$firstPack, &$ip, &$port, $buffer) {
                    $this->logger->debug('connected to voice UDP');
                    $this->client = $client;

                    $this->loop->addTimer(0.1, function () use (&$client, $buffer) {
                        $client->send((string) $buffer);
                    });

                    $this->udpHeartbeat = $this->loop->addPeriodicTimer(5, function () use ($client) {
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

                        $this->logger->debug('received our IP and port', ['ip' => $ip, 'port' => $port]);

                        if (! function_exists('\Sodium\crypto_secretbox')) {
                            $this->logger->error('libsodium was not found, closing');
                            $this->emit('error', [new LibSodiumNotFoundException('libsodium-php could not be found.')]);
                            $this->client->close();
                            $this->voiceWebsocket->close();

                            $this->mainSend([
                                'op' => Op::OP_VOICE_STATE_UPDATE,
                                'd'  => [
                                    'guild_id'   => $this->channel->guild_id,
                                    'channel_id' => null,
                                    'self_mute'  => true,
                                    'self_deaf'  => true,
                                ],
                            ]);

                            return;
                        }

                        $payload = [
                            'op' => Op::VOICE_SELECT_PROTO,
                            'd'  => [
                                'protocol' => 'udp',
                                'data'     => [
                                    'address' => $ip,
                                    'port'    => (int) $port,
                                    'mode'    => $this->mode,
                                ],
                            ],
                        ];

                        $this->send($payload);

                        $client->removeListener('message', $decodeUDP);

                        if (! $this->deaf) {
                            $client->on('message', [$this, 'handleAudioData']);
                        }
                    };

                    $client->on('message', $decodeUDP);
                }, function ($e) {
                    $this->logger->error('error while connecting to udp', ['e' => $e->getMessage()]);
                    $this->emit('error', [$e]);
                });
            }
        };

        $ws->on('message', $discoverUdp);
        $ws->on('message', function ($message) {
            $data = json_decode($message->getPayload());

            $this->emit('ws-message', [$message, $this]);

            switch ($data->op) {
                case Op::VOICE_HEARTBEAT: // keepalive response
                    $end = microtime(true);
                    $start = $data->d;
                    $diff = ($end - $start) * 1000;

                    if ($diff <= 10) { // set to 20ms
                        $this->setFrameSize(20);
                    } elseif ($diff <= 20) { // set to 40ms
                        $this->setFrameSize(40);
                    } else { // set to 60ms
                        $this->setFrameSize(60);
                    }

                    $this->emit('ws-ping', [$diff]);
                    break;
                case Op::VOICE_DESCRIPTION: // ready
                    $this->ready = true;
                    $this->mode = $data->d->mode;
                    $this->secret_key = '';

                    foreach ($data->d->secret_key as $part) {
                        $this->secret_key .= pack('C*', $part);
                    }

                    $this->logger->debug('received description packet, vc ready', ['data' => json_decode(json_encode($data->d), true)]);

                    if (! $this->reconnecting) {
                        $this->emit('ready', [$this]);
                    } else {
                        $this->reconnecting = false;
                        $this->emit('resumed', [$this]);
                    }

                    break;
                case Op::VOICE_SPEAKING: // user started speaking
                    $this->emit('speaking', [$data->d->speaking, $data->d->user_id, $this]);
                    $this->emit("speaking.{$data->d->user_id}", [$data->d->speaking, $this]);
                    $this->speakingStatus[$data->d->ssrc] = $data->d;
                    break;
            }
        });

        $ws->on('error', function ($e) {
            $this->logger->error('error with voice websocket', ['e' => $e->getMessage()]);
            $this->emit('ws-error', [$e]);
        });

        $ws->on('close', function ($op, $reason) {
            $this->logger->warning('voice websocket closed', ['op' => $op, 'reason' => $reason]);
            $this->emit('ws-close', [$op, $reason, $this]);
        });

        if (! $this->sentLoginFrame) {
            $payload = [
                'op' => Op::VOICE_IDENTIFY,
                'd'  => [
                    'server_id'  => $this->channel->guild_id,
                    'user_id'    => $this->data['user_id'],
                    'session_id' => $this->data['session'],
                    'token'      => $this->data['token'],
                ],
            ];

            $this->logger->debug('sending identify', ['packet' => $payload]);

            $this->send($payload);
            $this->sentLoginFrame = true;
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
        $this->logger->error('error with voice websocket', ['e' => $e->getMessage()]);
        $this->emit('error', [$e]);
    }

    /**
     * Handles a voice server change.
     *
     * @param array $data New voice server information.
     *
     * @return void
     */
    public function handleVoiceServerChange(array $data = [])
    {
        $this->logger->debug('voice server has changed, dynamically changing servers in the background', ['data' => $data]);
        $this->reconnecting   = true;
        $this->sentLoginFrame = false;
        $this->pause();

        $this->client->close();
        $this->voiceWebsocket->close();

        $this->loop->cancelTimer($this->heartbeat);
        $this->loop->cancelTimer($this->udpHeartbeat);

        $this->data['token'] = $data['token']; // set the token if it changed
        $this->endpoint      = str_replace([':80', ':443'], '', $data['endpoint']);

        $this->initSockets();

        $this->on('resumed', function () {
            $this->logger->debug('voice client resumed');
            $this->unpause()->then(function () {
                $this->speaking = false;
                $this->setSpeaking(true);
            });
        });
    }

    /**
     * Plays a file on the voice stream.
     *
     * @param string $file     The file to play.
     * @param int    $channels How many audio channels to encode with.
     *
     * @throws FileNotFoundException Thrown when the file specified could not be found.
     *
     * @return \React\Promise\Promise
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

        $process = $this->dcaEncode($file, $channels);
        $process->start($this->loop);

        return $this->playDCAStream($process);
    }

    /**
     * Plays a raw PCM16 stream.
     *
     * @param resource|Stream $stream   The stream to be encoded and sent.
     * @param int             $channels How many audio channels to encode with.
     *
     * @throws \RuntimeException Thrown when the stream passed to playRawStream is not a valid resource.
     *
     * @return \React\Promise\Promise
     */
    public function playRawStream($stream, $channels = 2)
    {
        $deferred = new Deferred();

        if (! $this->ready) {
            $deferred->reject(new \Exception('Voice Client is not ready.'));

            return $deferred->promise();
        }

        if (! is_resource($stream) && ! $stream instanceof Stream) {
            $deferred->reject(new \RuntimeException('The stream passed to playRawStream was not an instance of resource or ReactPHP Stream.'));

            return $deferred->promise();
        }

        if (is_resource($stream)) {
            $stream = new Stream($stream, $this->loop);
        }

        $process = $this->dcaEncode('', $channels);
        $process->start($this->loop);

        $stream->pipe($process->stdin);

        return $this->playDCAStream($process);
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

        if (! $this->ready) {
            $deferred->reject(new \Exception('Voice Client is not ready.'));

            return $deferred->promise();
        }

        if ($stream instanceof Process) {
            $stream->stderr->on('data', function ($d) {
                if (empty($d)) {
                    return;
                }

                $this->emit('stderr', [$d, $this]);
            });

            $deferred->promise()->then(function () use ($stream) {
                $stream->close();
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

        $count        = 0;
        $noData       = false;
        $noDataHeader = false;

        $this->setSpeaking(true);

        $processff2opus = function () use (&$processff2opus, $stream, &$noData, &$noDataHeader, $deferred, &$count) {
            if ($this->isPaused) {
                $this->loop->addTimer($this->frameSize / 1000, $processff2opus);

                return;
            }

            if ($this->stopAudio) {
                $this->setSpeaking(false);
                fclose($stream);

                $this->seq        = 0;
                $this->timestamp  = 0;
                $this->streamTime = 0;
                $this->startTime  = null;

                $this->stopAudio = false;
                $deferred->resolve(true);

                return;
            }

            $header = @fread($stream, 2);

            if (! $header) {
                if ($noDataHeader && $this->streamTime != 0) {
                    $this->setSpeaking(false);
                    fclose($stream);

                    $this->seq        = 0;
                    $this->timestamp  = 0;
                    $this->streamTime = 0;
                    $this->startTime  = null;

                    $deferred->resolve(false);
                } else {
                    $noDataHeader = true;
                    $this->loop->addTimer($this->frameSize / 1000, $processff2opus);
                }

                return;
            }

            $opusLength = unpack('v', $header);
            $opusLength = reset($opusLength);
            $buffer     = fread($stream, $opusLength);

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

            if (($this->timestamp + ($this->frameSize * 48)) < 4294967295) {
                $this->timestamp += $this->frameSize * 48;
            } else {
                $this->timestamp = 0;
            }

            $this->streamTime = $count * $this->frameSize;

            $this->loop->addTimer($this->startTime + $this->streamTime / 1000 - microtime(true), $processff2opus);
        };

        $readMagicBytes = false;
        $readJsonLeng   = false;

        $jsonLen  = 0;
        $jsonBuff = '';

        $this->loop->addReadStream($stream, function ($stream) use ($deferred, &$readMagicBytes, &$readJsonLeng, &$jsonLen, &$jsonBuff, $processff2opus) {
            if (! $readMagicBytes) {
                $magicBytes = fread($stream, 4);

                if ($magicBytes !== self::DCA_VERSION) {
                    $deferred->reject(new OutdatedDCAException('You are using an outdated version of DCA. Please make sure you have the latest version from https://github.com/bwmarrin/dca'));

                    return;
                }

                $readMagicBytes = true;

                return;
            }

            if (! $readJsonLeng) {
                $len = fread($stream, 4);
                $len = unpack('l', $len);
                $jsonLen = reset($len);

                $readJsonLeng = true;

                return;
            }

            $jsonBuffTemp = fread($stream, $jsonLen);
            $buffTempLeng = strlen($jsonBuffTemp);
            $jsonBuff .= $jsonBuffTemp;

            if ($buffTempLeng < $jsonLen) {
                $jsonLen -= $buffTempLeng;

                return;
            }

            $json = json_decode($jsonBuff, true);

            if (! is_null($json)) {
                $this->frameSize = $json['opus']['frame_size'] / 48;

                $deferred->notify($json);
            }

            $this->loop->removeReadStream($stream);
            $this->loop->addTimer(0.5, $processff2opus);

            $this->startTime = microtime(true) + 0.5;
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
        if (! $this->ready) {
            return;
        }

        $packet = new VoicePacket($data, $this->ssrc, $this->seq, $this->timestamp, true, $this->secret_key);
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
            'op' => Op::VOICE_SPEAKING,
            'd'  => [
                'speaking' => $speaking,
                'delay'    => 0,
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

        $this->mainSend([
            'op' => Op::OP_VOICE_STATE_UPDATE,
            'd'  => [
                'guild_id'   => $channel->guild_id,
                'channel_id' => $channel->id,
                'self_mute'  => $this->mute,
                'self_deaf'  => $this->deaf,
            ],
        ]);

        $this->channel = $channel;

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Sets the frame size.
     *
     * Options (in ms):
     * - 20
     * - 40
     * - 60
     *
     * @param int $fs The frame size to set.
     *
     * @return \React\Promise\Promise
     */
    public function setFrameSize($fs)
    {
        $deferred = new Deferred();

        $legal = [20, 40, 60];

        if (false === array_search($fs, $legal)) {
            $deferred->reject(new \InvalidArgumentException("{$fs} is not a valid option. Valid options are: ".trim(implode(', ', $legal), ', ')));

            return $deferred->promise();
        }

        if ($this->speaking) {
            $deferred->reject(new \Exception('Cannot change frame size while playing.'));

            return $deferred->promise();
        }

        $this->frameSize = $fs;

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Sets the bitrate.
     *
     * @param int $bitrate The bitrate to set.
     *
     * @return \React\Promise\Promise
     */
    public function setBitrate($bitrate)
    {
        $deferred = new Deferred();

        if ($bitrate > 128000 || $bitrate < 8000) {
            $deferred->reject(new \InvalidArgumentException("{$bitrate} is not a valid option. The bitrate must be between 8,000bpm and 128,000bpm."));

            return $deferred->promise();
        }

        if ($this->speaking) {
            $deferred->reject(new \Exception('Cannot change bitrate while playing.'));

            return $deferred->promise();
        }

        $this->bitrate = $bitrate;

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Sets the volume.
     *
     * @param int $volume The volume to set.
     *
     * @return \React\Promise\Promise
     */
    public function setVolume($volume)
    {
        $deferred = new Deferred();

        if ($volume > 100 || $volume < 0) {
            $deferred->reject(new \InvalidArgumentException("{$volume}% is not a valid option. The bitrate must be between 0% and 100%."));

            return $deferred->promise();
        }

        if ($this->speaking) {
            $deferred->reject(new \Exception('Cannot change volume while playing.'));

            return $deferred->promise();
        }

        $this->volume = $volume;

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Sets the audio application.
     *
     * @param string $app The audio application to set.
     *
     * @return \React\Promise\Promise
     */
    public function setAudioApplication($app)
    {
        $deferred = new Deferred();

        $legal = ['voip', 'audio', 'lowdelay'];

        if (false === array_search($app, $legal)) {
            $deferred->reject(new \InvalidArgumentException("{$app} is not a valid option. Valid options are: ".trim(implode(', ', $legal), ', ')));

            return $deferred->promise();
        }

        if ($this->speaking) {
            $deferred->reject(new \Exception('Cannot change audio application while playing.'));

            return $deferred->promise();
        }

        $this->audioApplication = $app;

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
        $json = json_encode($data);
        $this->voiceWebsocket->send($json);
    }

    /**
     * Sends a message to the main websocket.
     *
     * @param array $data The data to send to the main WebSocket.
     *
     * @return void
     */
    public function mainSend(array $data)
    {
        $json = json_encode($data);
        $this->mainWebsocket->send($json);
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

        $this->mainSend([
            'op' => Op::OP_VOICE_STATE_UPDATE,
            'd'  => [
                'guild_id'   => $this->channel->guild_id,
                'channel_id' => $this->channel->id,
                'self_mute'  => $mute,
                'self_deaf'  => $deaf,
            ],
        ]);

        $this->client->removeListener('message', [$this, 'handleAudioData']);

        if (! $deaf) {
            $this->client->on('message', [$this, 'handleAudioData']);
        }

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

        if (! $this->speaking) {
            $deferred->reject(new \Exception('Audio must be playing to pause it.'));

            return $deferred->promise();
        }

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

        if (! $this->speaking) {
            $deferred->reject(new \Exception('Audio must be playing to unpause it.'));

            return $deferred->promise();
        }

        $this->isPaused  = false;
        $this->timestamp = microtime(true) * 1000;
        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Stops the current sound.
     *
     * @return \React\Promise\Promise
     */
    public function stop()
    {
        $deferred = new Deferred();

        if ($this->stopAudio) {
            $deferred->reject(new \Exception('Audio is already being stopped.'));

            return $deferred->promise();
        }

        if (! $this->speaking) {
            $deferred->reject(new \Exception('Audio must be playing to stop it.'));

            return $deferred->promise();
        }

        $this->stopAudio = true;

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Closes the voice client.
     *
     * @return \React\Promise\Promise
     */
    public function close()
    {
        $deferred = new Deferred();

        if (! $this->ready) {
            $deferred->reject(new \Exception('Voice Client is not connected.'));

            return $deferred->promise();
        }

        $this->stop();
        $this->setSpeaking(false);
        $this->ready = false;

        $this->mainSend([
            'op' => Op::OP_VOICE_STATE_UPDATE,
            'd'  => [
                'guild_id'   => $this->channel->guild_id,
                'channel_id' => null,
                'self_mute'  => true,
                'self_deaf'  => true,
            ],
        ]);

        $this->client->close();
        $this->voiceWebsocket->close();

        $this->heartbeat_interval = null;
        $this->loop->cancelTimer($this->heartbeat);
        $this->loop->cancelTimer($this->udpHeartbeat);
        $this->heartbeat      = null;
        $this->udpHeartbeat   = null;
        $this->seq            = 0;
        $this->timestamp      = 0;
        $this->sentLoginFrame = false;
        $this->startTime      = null;
        $this->streamTime     = 0;
        $this->speakingStatus = new Collection([], 'ssrc');

        $this->emit('close');

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Checks if the user is speaking.
     *
     * @param int $id Either the User ID or SSRC.
     *
     * @return bool Whether the user is speaking.
     */
    public function isSpeaking($id)
    {
        $ssrc = @$this->speakingStatus[$id];
        $user = $this->speakingStatus->get('user_id', $id);

        if (is_null($ssrc) && ! is_null($user)) {
            return $user->speaking;
        } elseif (is_null($user) && ! is_null($ssrc)) {
            return $user->speaking;
        } elseif (is_null($user) && is_null($ssrc)) {
            return $user->speaking;
        } else {
            return false;
        }
    }

    /**
     * Handles a voice state update.
     *
     * @param object $data The WebSocket data.
     *
     * @return void
     */
    public function handleVoiceStateUpdate($data)
    {
        $removeDecoder = function ($ss) {
            $decoder = @$this->voiceDecoders[$ss->ssrc];

            if (is_null($decoder)) {
                return; // no voice decoder to remove
            }

            $decoder->close();
            unset($this->voiceDecoders[$ss->ssrc]);
            unset($this->speakingStatus[$ss->ssrc]);
        };

        $ss = $this->speakingStatus->get('user_id', $data->user_id);

        if (is_null($ss)) {
            return; // not in our channel
        }

        if ($data->channel_id == $this->channel->id) {
            return; // ignore, just a mute/deaf change
        }

        $removeDecoder($ss);
    }

    /**
     * Gets a recieve voice stream.
     *
     * @param int|string $id Either a SSRC or User ID.
     *
     * @return React\Promise\Promise
     */
    public function getRecieveStream($id)
    {
        $deferred = new Deferred();

        if (isset($this->recieveStreams[$id])) {
            $deferred->resolve($this->recieveStreams[$id]);

            return $deferred->promise();
        }

        foreach ($this->speakingStatus as $status) {
            if ($status->user_id == $id) {
                $deferred->resolve($this->recieveStreams[$status->ssrc]);

                return $deferred->promise();
            }
        }

        $deferred->reject(new \Exception("Could not find a recieve stream with the ID \"{$id}\"."));

        return $deferred->promise();
    }

    /**
     * Handles raw opus data from the UDP server.
     *
     * @param string $message The data from the UDP server.
     *
     * @return void
     */
    protected function handleAudioData($message)
    {
        $voicePacket = VoicePacket::make($message);
        $nonce       = new Buffer(24);
        $nonce->write($voicePacket->getHeader(), 0);
        $message = \Sodium\crypto_secretbox_open($voicePacket->getData(), (string) $nonce, $this->secret_key);

        if ($message === false) {
            // if we can't decode the message, drop it silently.
            return;
        }

        $this->emit('raw', [$message, $this]);

        $vp      = VoicePacket::make($voicePacket->getHeader().$message);
        $ss      = $this->speakingStatus->get('ssrc', $vp->getSSRC());
        $decoder = @$this->voiceDecoders[$vp->getSSRC()];

        if (is_null($ss)) {
            // for some reason we don't have a speaking status
            return;
        }

        if (is_null($decoder)) {
            // make a decoder
            if (! isset($this->recieveStreams[$ss->ssrc])) {
                $this->recieveStreams[$ss->ssrc] = new RecieveStream();

                $this->recieveStreams[$ss->ssrc]->on('pcm', function ($d) {
                    $this->emit('channel-pcm', [$d, $this]);
                });

                $this->recieveStreams[$ss->ssrc]->on('opus', function ($d) {
                    $this->emit('channel-opus', [$d, $this]);
                });
            }

            $createDecoder = function () use (&$createDecoder, $ss) {
                $decoder = $this->dcaDecode();
                $decoder->start($this->loop);

                $decoder->stdout->on('data', function ($data) use ($ss) {
                    $this->recieveStreams[$ss->ssrc]->writePCM($data);
                });
                $decoder->stderr->on('data', function ($data) use ($ss) {
                    $this->emit("voice.{$ss->ssrc}.stderr", [$data, $this]);
                    $this->emit("voice.{$ss->user_id}.stderr", [$data, $this]);
                });
                $decoder->on('exit', function ($code, $term) use ($ss, &$createDecoder) {
                    if ($code > 0) {
                        $this->emit('decoder-error', [$code, $term, $ss]);

                        $createDecoder();
                    }
                });

                $this->voiceDecoders[$ss->ssrc] = $decoder;
            };

            $createDecoder();
            $decoder = @$this->voiceDecoders[$vp->getSSRC()];
        }

        $buff = new Buffer(strlen($vp->getData()) + 2);
        $buff->write(pack('s', strlen($vp->getData())), 0);
        $buff->write($vp->getData(), 2);

        $decoder->stdin->write((string) $buff);
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
     * @throws \Discord\Exceptions\FFmpegNotFoundException Thrown when FFmpeg is not found.
     *
     * @return bool Whether FFmpeg is installed or not.
     */
    public function checkForFFmpeg()
    {
        $binaries = [
            'ffmpeg',
        ];

        foreach ($binaries as $binary) {
            $output = shell_exec("which {$binary}");

            if (! empty($output)) {
                return true;
            }
        }

        $this->emit('error', [new FFmpegNotFoundException('No FFmpeg binary was found.')]);
    }

    /**
     * Checks if DCA is installed.
     *
     * @throws \Discord\Exceptions\DCANotFoundException Thrown when DCA is not found.
     *
     * @return bool Whether DCA is installed or not.
     */
    public function checkForDCA()
    {
        $binaries = [
            'Darwin' => [
                32 => 'dca-v0.1.0-darwin-10.6-386',
                64 => 'dca-v0.1.0-darwin-10.6-amd64',
            ],
            'Linux' => [
                32 => 'dca-v0.1.0-linux-386',
                64 => 'dca-v0.1.0-linux-amd64',
            ],
        ];

        if (array_key_exists(PHP_OS, $binaries)) {
            $binary = realpath(__DIR__.'/../../../bin/'.$binaries[PHP_OS][PHP_INT_SIZE * 8]);

            $this->dca = $binary;

            return;
        }

        $this->emit('error', [new DCANotFoundException('No DCA binary was found that is compatible with your operating system and arch.')]);
    }

    /**
     * Checks if libsodium-php is installed.
     *
     * @throws \Discord\Exceptions\LibSodiumNotFoundException Thrown when libsodium-php is not found.
     */
    public function checkForLibsodium()
    {
        if (! function_exists('\Sodium\crypto_secretbox')) {
            throw new LibSodiumNotFoundException('libsodium-php could not be found.');
        }
    }

    /**
     * Encodes a file to Opus with DCA.
     *
     * @param string $filename The file name that will be encoded.
     * @param int    $channels How many audio channels to encode with.
     *
     * @return Process A ReactPHP Child Process
     */
    public function dcaEncode($filename = '', $channels = 2)
    {
        // if (! empty($filename) && ! file_exists($filename)) {
        //     return;
        // }

        $flags = [
             '-ac', $channels, // Channels
             '-aa', $this->audioApplication, // Audio application
             '-ab', round($this->bitrate / 1000), // Bitrate
             '-as', round($this->frameSize * 48), // Frame Size
            '-vol', round($this->volume * 2.56), // Volume
              '-i', (empty($filename)) ? 'pipe:0' : "\"{$filename}\"", // Input file
        ];

        $flags = implode(' ', $flags);

        return new Process("{$this->dca} {$flags}");
    }

    /**
     * Decodes a file from Opus with DCA.
     *
     * @param int $channels  How many audio channels to decode with.
     * @param int $frameSize The Opus packet frame size.
     *
     * @return Process A ReactPHP Child Process
     */
    public function dcaDecode($channels = 2, $frameSize = null)
    {
        if (is_null($frameSize)) {
            $frameSize = round($this->frameSize * 48);
        }

        $flags = [
            '-ac', $channels, // Channels
            '-ab', round($this->bitrate / 1000), // Bitrate
            '-as', $frameSize, // Frame Size
            '-mode', 'decode', // Decode mode
        ];

        $flags = implode(' ', $flags);

        return new Process("{$this->dca} {$flags}");
    }

    /**
     * Returns the connected channel.
     *
     * @return Channel The connected channel.
     */
    public function getChannel()
    {
        return $this->channel;
    }
}
