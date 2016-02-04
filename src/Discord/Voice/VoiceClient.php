<?php

namespace Discord\Voice;

use Discord\Exceptions\FFmpegNotFoundException;
use Discord\Exceptions\FileNotFoundException;
use Discord\Exceptions\FormatNotSupportedException;
use Discord\Exceptions\OpusNotFoundException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Voice\Buffer;
use Discord\Voice\VoicePacket;
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

class VoiceClient extends EventEmitter
{
	/**
	 * The FFmpeg binary name that we will use.
	 *
	 * @var string 
	 */
	protected $binary;

	/**
	 * The ReactPHP event loop.
	 *
	 * @var LoopInterface 
	 */
	protected $loop;

	/**
	 * The main WebSocket instance.
	 *
	 * @var WebSocket 
	 */
	protected $mainWebsocket;

	/**
	 * The voice WebSocket instance.
	 *
	 * @var WS
	 */
	protected $voiceWebsocket;

	/**
	 * The UDP client.
	 *
	 * @var Socket 
	 */
	protected $client;

	/**
	 * The Channel that we are connecting to.
	 *
	 * @var Channel
	 */
	protected $channel;

	/**
	 * Data from the main WebSocket.
	 *
	 * @var array 
	 */
	protected $data;

	/**
	 * The Voice WebSocket endpoint.
	 *
	 * @var string 
	 */
	protected $endpoint;

	/**
	 * The port the UDP client will use.
	 *
	 * @var integer 
	 */
	protected $udpPort;

	/**
	 * The UDP heartbeat interval.
	 *
	 * @var integer 
	 */
	protected $heartbeat_interval;

	/**
	 * The SSRC value.
	 *
	 * @var integer 
	 */
	protected $ssrc;

	/**
	 * The sequence of audio packets being sent.
	 *
	 * @var integer 
	 */
	protected $seq = 0;

	/**
	 * The timestamp of the last packet.
	 *
	 * @var integer 
	 */
	protected $timestamp = 0;

	/**
	 * The Voice WebSocket mode.
	 *
	 * @var string 
	 */
	protected $mode = 'plain';

	/**
	 * Are we currently set as speaking?
	 *
	 * @var boolean 
	 */
	protected $speaking = false;

	/**
	 * Have we sent the login frame yet?
	 *
	 * @var boolean 
	 */
	protected $sentLoginFrame = false;

	/**
	 * The time we started sending packets.
	 *
	 * @var epoch
	 */
	protected $startTime;

	/**
	 * The stream time of the last packet.
	 *
	 * @var int 
	 */
	protected $streamTime = 0;

	/**
	 * The volume percentage the audio will be encoded with.
	 *
	 * @var integer 
	 */
	public $volume = 70;

	/**
	 * Constructs the Voice Client instance.
	 *
	 * @param WebSocket $websocket 
	 * @param LoopInterface $loop
	 * @param Guild $guild 
	 * @param Channel $channel  
	 * @param array $data
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
		$resolver = (new DNSFactory)->createCached('8.8.8.8', $loop);
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
							'd' => null
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
											'mode' => $this->mode
										]
									]
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

			if (!$this->sentLoginFrame) {
				$this->send([
					'op' => 0,
					'd' => [
						'server_id' => $this->channel->guild_id,
						'user_id' => $this->data['user_id'],
						'session_id' => $this->data['session'],
						'token' => $this->data['token']
					]
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
	 * @param string $file 
	 * @param int $channels
	 * @return \React\Promise\Promise
	 */
	public function playFile($file, $channels = 2)
	{
		$deferred = new Deferred();

		if (!file_exists($file)) {
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
	 * @param resource $stream
	 * @param int $channels 
	 * @return \React\Promise\Promise 
	 */
	public function playRawStream($stream, $channels = 2)
	{
		$deferred = new Deferred();

		if (!is_resource($stream)) {
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
				'-af', 'volume=' . ($this->volume / 100), // Volume
				'pipe:1' // Pipe to stdout
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

			if (!$buffer) {
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

			if (!$this->speaking) {
				$this->setSpeaking(true);
			}

			if (strlen($buffer) !== 1920 * $channels) {
				dump(strlen($buffer));
				$newbuff = new Buffer(1920 * $channels);
				$newbuff->write($buffer, 0);
				$buffer = (string) $newbuff;
			}

			$count++;

			if (($this->seq + 1) < 65535) {
				$this->seq++;
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
	 * @param string $data 
	 * @return void 
	 */
	public function sendBuffer($data)
	{
		dump($this->ssrc, $this->seq, $this->timestamp);
		$packet = new VoicePacket($data, $this->ssrc, $this->seq, $this->timestamp);
		$this->client->send((string) $packet);
	}

	/**
	 * Sets the speaking value of the client.
	 *
	 * @param boolean $speaking
	 * @return boolean 
	 */
	public function setSpeaking($speaking = true)
	{
		if ($this->speaking == $speaking) {
			return $speaking;
		}

		$this->send([
			'op' => 5,
			'd' => [
				'speaking' => false,
				'delay' => 0
			]
		]);

		$this->speaking = $speaking;

		return $speaking;
	}

	/**
	 * Sends a message to the voice websocket.
	 *
	 * @param array $data 
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
	 * @return boolean
	 */
	public function checkForFFmpeg()
	{
		$binaries = [
			'ffmpeg'
		];

		foreach ($binaries as $binary) {
			$output = shell_exec("which {$binary}");

			if (!empty($output)) {
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
	 * @param string|array $parameters 
	 * @param boolean $advanced 
	 * @return \React\Stream\Stream 
	 */
	public function ffExec($parameters, $advanced = true)
	{
		$command = "{$this->binary} -loglevel 0 ";

		foreach ((array) $parameters as $param) {
			$command .= "{$param} ";
		}

		if (!$advanced) {
			return shell_exec(trim($command));
		}

		return new Process(trim($command));
	}
}