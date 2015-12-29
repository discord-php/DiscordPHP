<?php

namespace Discord\Helpers;

class Endpoints
{
	const BASE_URL = 'https://discordapp.com/api/';
	const REGEX = '/:([a-z_]+)/';

	public static function getEndpoint($url, $params)
	{
		$url = self::BASE_URL . $url;
		$string = '';

		if (preg_match_all(self::REGEX, $url, $matches)) {
			$original = $matches[0];
			$vars = $matches[1];

			foreach ($vars as $key => $var) {
				if ($attribute = $params[$var]) {
					$string .= str_replace($original[$key], $attribute, $url);
				}
			}
		}

		return $string;
	}
}