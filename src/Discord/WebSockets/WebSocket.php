<?php

namespace Discord\WebSockets;

use Discord\Discord;
use Discord\Helpers\Guzzle;
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
			$ws->on('message', function ($data, $ws) {
				$this->emit('raw', [$data, $ws, $this->discord]);
				$data = json_decode($data);

				if (!is_null($handler = $this->handlers->getHandler($data->t))) {
					$handler = new $handler($data->d);
					$this->emit($data->t, [$handler->getData($data->d), $ws, $this->discord]);
				}

				if ($data->t == Event::READY) {
					$tts = $data->d->heartbeat_interval / 1000;
					$this->loop->addPeriodicTimer($tts, function () use ($ws) {
						$this->send($ws, [
							'op' => 1,
							'd' => microtime(true) * 1000
						]);
					});
				}
			});

			$ws->on('close', function ($ws) {
				$this->emit('close', [$ws, $this->discord]);
			});

			$ws->on('error', function ($error, $ws) {
				$this->emit('error', [$error, $ws, $this->discord]);
			});

			if (!$this->sentLoginFrame) {
				$this->sendLoginFrame($ws);
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
	 * @param WebSocketInstance $ws 
	 * @return void 
	 */
	public function sendLoginFrame($ws)
	{
		$this->send($ws, [
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
	 * @param WebSocketInstance $ws 
	 * @param array $data 
	 * @return void 
	 */
	public function send($ws, $data)
	{
		$frame = new Frame(json_encode($data), true);
		$ws->send($frame);
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