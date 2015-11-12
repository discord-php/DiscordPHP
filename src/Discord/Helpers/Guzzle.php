<?php

namespace Discord\Helpers;

use Discord\Exceptions\DiscordRequestFailedException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

class Guzzle
{
	static protected $base_url = 'https://discordapp.com/api';

	/**
	 * Handles dynamic calls to the class.
	 *
	 * @param string $url
	 * @param array $params 
	 * @param boolean $noauth
	 */
	public static function __callStatic($name, $params)
	{
		$url = $params[0];
		$content = @$params[1];
		$auth = @$params[2];

		$guzzle = new GuzzleClient();
		$headers = [];

		if (is_null($content)) {
			$content = [];
		}

		if (!$auth) {
			$headers['authorization'] = DISCORD_TOKEN;
		}

		try {
			$request = $guzzle->request($name, self::$base_url.'/'.$url, [
				'headers' => $headers,
				'json' => $content
			]);

			if ($request->getStatusCode() < 200 || $request->getStatusCode() > 226) {
				self::handleError($request->getStatusCode(), 'A status code outside of 200 to 226 was returned.');
			}
		} catch (\RuntimeException $e) {
			if ($e->hasResponse()) {
				self::handleError($e->getCode(), $e->getResponse());
			} else {
				self::handleError($e->getCode(), $e->getMessage());
			}
		}

		return json_decode($request->getBody());
	}

	/**
	 * Handles an error code.
	 *
	 * @param integer $error_code 
	 * @param string $message
	 */
	public static function handleError($error_code, $message)
	{
		switch ($error_code) {
			case 400:
				throw new DiscordRequestFailedException("Error code {$error_code}: This usually means you have entered an incorrect Email or Password.");
				break;
			default:
				throw new DiscordRequestFailedException("Erorr code {$error_code}: There was an error processing the request. {$message->getReasonPhrase()}");
				break;
		}
	}
}