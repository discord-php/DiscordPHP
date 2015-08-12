<?php

namespace Discord;

class Guzzle
{
	public static function post($uri, $params)
	{
		$client = new \GuzzleHttp\Client();
		$request = $client->post('https://discordapp.com/api/' . $uri, $params);
		return $request;
	}

	public static function get($uri, $params)
	{
		$client = new \GuzzleHttp\Client();
		$request = $client->get('https://discordapp.com/api/' . $uri, $params);
		return $request;
	}
}