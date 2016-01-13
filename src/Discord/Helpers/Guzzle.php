<?php

namespace Discord\Helpers;

use Discord\Discord;
use Discord\Exceptions\DiscordRequestFailedException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

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
        $content = (isset($params[1])) ? $params[1] : null;
        $auth = (isset($params[1])) ? true : false;

        return self::runRequest($name, $url, $content, $auth);
    }

    public static function runRequest($method, $url, $content, $auth)
    {
        $guzzle = new GuzzleClient(['http_errors' => false, 'allow_redirects' => true]);
        $url = self::$base_url."/{$url}";
        
        $headers = [
            'User-Agent' => 'DiscordPHP/' . Discord::VERSION . ' DiscordBot (https://github.com/teamreflex/DiscordPHP, ' . Discord::VERSION . ')',
            'Content-Type' => 'application/json'
        ];

        if (!$auth) {
            $headers['authorization'] = DISCORD_TOKEN;
        }

        $done = false;
        $finalRes = null;

        while (!$done) {
            $content = (is_null($content)) ? null : json_encode($content);
            $request = new Request($method, $url, $headers, $content);
            $response = $guzzle->send($request);
            
            // Rate limiting
            if ($response->getStatusCode() == 429) {
                $tts = $response->getHeader('Retry-After') * 1000;
                usleep($tts);
                continue;
            }

            // Not good!
            if ($response->getStatusCode() < 200 && $response->getStatusCode() > 226) {
                self::handleError($response->getStatusCode(), $response->getReasonPhrase());
                continue;
            }

            $done = true;
            $finalRes = $response;
        }

        return json_decode($finalRes->getBody());
    }

    /**
     * Handles an error code.
     *
     * @param integer $error_code 
     * @param string $message
     */
    public static function handleError($error_code, $message)
    {
        if (!is_string($message)) {
            $message = $message->getReasonPhrase();
        }

        switch ($error_code) {
            case 400:
                throw new DiscordRequestFailedException("Error code {$error_code}: This usually means you have entered an incorrect Email or Password. {$message}");
                break;
            default:
                throw new DiscordRequestFailedException("Erorr code {$error_code}: There was an error processing the request. {$message}");
                break;
        }
    }
}
