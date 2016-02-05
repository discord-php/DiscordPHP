<?php

namespace Discord\Helpers;

use Discord\Discord;
use Discord\Exceptions\ContentTooLongException;
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
    public static $base_url = 'https://discordapp.com/api';

    /**
     * Handles dynamic calls to the class.
     *
     * @param string $url
     * @param array $params 
     * @param boolean $noauth
     * @return object 
     */
    public static function __callStatic($name, $params)
    {
        $url = $params[0];
        $content = (isset($params[1])) ? $params[1] : null;
        $auth = (isset($params[2])) ? true : false;

        return self::runRequest($name, $url, $content, $auth);
    }

    /**
     * Runs http calls.
     * 
     * @param  string $method  
     * @param  string $url     
     * @param  array $content 
     * @param  boolean $auth    
     * @return object
     */
    public static function runRequest($method, $url, $content, $auth)
    {
        $guzzle = new GuzzleClient(['http_errors' => false, 'allow_redirects' => true]);
        $url = self::$base_url."/{$url}";

        $headers = [
            'User-Agent' => self::getUserAgent(),
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
            if ($response->getStatusCode() < 200 || $response->getStatusCode() > 226) {
                self::handleError($response->getStatusCode(), $response->getReasonPhrase(), $response->getBody(true));
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
     * @param string $content 
     * @throws DiscordRequestFailedException 
     */
    public static function handleError($error_code, $message, $content)
    {
        if (!is_string($message)) {
            $message = $message->getReasonPhrase();
        }

        $message .= " - {$content}";

        if (false !== strpos($content, 'longer than 2000 characters') && $error_code == 500) {
            // Discord has set a restriction with content sent over REST,
            // if it is more than 2000 characters long it will not be
            // sent and will return a 500 error.
            // 
            // There is no way around this, you must use WebSockets.
            throw new ContentTooLongException('The expected content was more than 2000 characters. Use websockets if you need this content.');
        }

        switch ($error_code) {
            case 400:
                $response = "Error code 400: This usually means you have entered an incorrect Email or Password. {$message}";
                break;
            case 500:
                $response = "Error code 500: This usually means something went wrong with Discord. {$message}";
                break;
            case 403:
                $response = "Erorr code 403: You do not have permission to do this. {$message}";
                break;
            default:
                $response = "Erorr code {$error_code}: There was an error processing the request. {$message}";
                break;
        }

        throw new DiscordRequestFailedException($response);
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
