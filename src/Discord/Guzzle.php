<?php

namespace Discord;

use Discord\Exceptions\GuzzleServerException;
use Discord\Exceptions\GuzzleURIException;

class Guzzle
{
	/**
	 * Sends a POST request to the specified URI
	 * @param URI [string]
	 * @param Parameters [array]
	 * @return GuzzleResponse
	 */
	public static function post($uri, $params)
	{
		$client = new \GuzzleHttp\Client();

		try {
			$request = $client->post('https://discordapp.com/api/' . $uri, $params);
		} catch (ClientErrorResponseException $e) {
			throw new GuzzleURIException($e->getMessage());
		} catch (ServerException $e) {
			throw new GuzzleServerException($e->getMessage());
		}

		return $request;
	}
	/**
	 * Sends a GET request to the specified URI
	 * @param URI [string]
	 * @param Parameters [array]
	 * @return GuzzleResponse
	 */
	public static function get($uri, $params)
	{
		$client = new \GuzzleHttp\Client();

		try {
			$request = $client->get('https://discordapp.com/api/' . $uri, $params);
		} catch (ClientErrorResponseException $e) {
			throw new GuzzleURIException($e->getMessage());
		} catch (ServerException $e) {
			throw new GuzzleServerException($e->getMessage());
		}

		return $request;
	}
}