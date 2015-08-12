<?php

namespace Discord;

class Guzzle
{
	public static function post($uri, $params)
	{
		$client = new \GuzzleHttp\Client(['base_uri' => 'https://discordapp.com/api/']);
		$request = $client->post($uri, $params);
		return $request;
	}

	public static function get($uri, $params)
	{
		$client = new \GuzzleHttp\Client(['base_uri' => 'https://discordapp.com/api/']);
		$request = $client->get($uri, $params);
		return $request;
	}
}