<?php

namespace Discord\Helpers;

use Discord\Discord;
use Discord\Exceptions\DiscordRequestFailedException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

class Guzzle
{
    /**
     * The Base URL of the API.
     * 
     * @var string
     */
    protected static $base_url = 'https://discordapp.com/api';

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
        $headers = [
            'User-Agent' => self::getUserAgent()
        ];

        if (is_null($content)) {
            $content = [];
        }

        if (!$auth) {
            $headers['authorization'] = DISCORD_TOKEN;
        }

        $finalRequest = null;
        $done = false;

        while (!$done) {
            try {
                $request = $guzzle->request($name, self::$base_url.'/'.$url, [
                    'headers'   => $headers,
                    'json'      => $content
                ]);

                if ($request->getStatusCode() < 200 || $request->getStatusCode() > 226 && $request->getStatusCode() != 429) {
                    return self::handleError($request->getStatusCode(), 'A status code outside of 200 to 226 was returned.');
                }

                if ($request->getStatusCode() == 429) {
                    $sleeptime = $request->header('Retry-After') * 1000;
                    usleep($sleeptime);
                }

                $done = true;
                $finalRequest = $request;
            } catch (\RuntimeException $e) {
                if ($e->hasResponse()) {
                    if ($e->getCode() != 429) {
                        self::handleError($e->getCode(), $e->getResponse());
                    }
                } else {
                    self::handleError($e->getCode(), $e->getMessage());
                }
            }
        }

        return json_decode($finalRequest->getBody());
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

    /**
     * Returns the User-Agent of the API.
     *
     * @return string 
     */
    public static function getUserAgent()
    {
        return 'DiscordPHP/' . Discord::VERSION . ' DiscordBot (https://github.com/teamreflex/DiscordPHP, ' . Discord::VERSION . ')';
    }
}
