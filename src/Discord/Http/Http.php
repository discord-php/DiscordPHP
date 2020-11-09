<?php

namespace Discord\Http;

use Discord\Discord;
use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Exceptions\Rest\ContentTooLongException;
use Discord\Exceptions\Rest\InvalidTokenException;
use Discord\Exceptions\Rest\NoPermissionsException;
use Discord\Exceptions\Rest\NotFoundException;
use Discord\Helpers\Deferred;
use Psr\Http\Message\ResponseInterface;
use React\Promise\ExtendedPromiseInterface;
use Throwable;

use function Discord\contains;

/**
 * Discord HTTP client.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class Http
{
    /**
     * Discord API base URL.
     *
     * @var string
     */
    const BASE_URL = 'https://discord.com/api/v'.Discord::HTTP_API_VERSION;

    /**
     * Authentication token.
     *
     * @var string
     */
    private $token;

    /**
     * HTTP driver.
     *
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Http wrapper constructor.
     *
     * @param string $token
     * @param DriverInterface|null $driver
     */
    public function __construct(string $token, DriverInterface $driver = null)
    {
        $this->token = $token;
        $this->driver = $driver;
    }

    /**
     * Sets the driver of the HTTP client.
     *
     * @param DriverInterface $driver
     */
    public function setDriver(DriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * Runs a GET request.
     *
     * @param string $url
     * @param mixed $content
     * @param array $headers
     *
     * @return ExtendedPromiseInterface
     */
    public function get(string $url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        return $this->runRequest('get', $url, $content, $headers);
    }

    /**
     * Runs a POST request.
     *
     * @param string $url
     * @param mixed $content
     * @param array $headers
     *
     * @return ExtendedPromiseInterface
     */
    public function post(string $url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        return $this->runRequest('post', $url, $content, $headers);
    }

    /**
     * Runs a PUT request.
     *
     * @param string $url
     * @param mixed $content
     * @param array $headers
     *
     * @return ExtendedPromiseInterface
     */
    public function put(string $url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        return $this->runRequest('put', $url, $content, $headers);
    }

    /**
     * Runs a PATCH request.
     *
     * @param string $url
     * @param mixed $content
     * @param array $headers
     *
     * @return ExtendedPromiseInterface
     */
    public function patch(string $url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        return $this->runRequest('patch', $url, $content, $headers);
    }

    /**
     * Runs a DELETE request.
     *
     * @param string $url
     * @param mixed $content
     * @param array $headers
     *
     * @return ExtendedPromiseInterface
     */
    public function delete(string $url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        return $this->runRequest('delete', $url, $content, $headers);
    }

    /**
     * Runs a request.
     *
     * @param string $method
     * @param string $url
     * @param mixed $content
     * @param array $headers
     *
     * @return ExtendedPromiseInterface
     */
    protected function runRequest(string $method, string $url, $content, array $headers = []): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        if (is_null($this->driver)) {
            $deferred->reject(new \Exception('HTTP driver is missing.'));

            return $deferred->promise();
        }

        $headers = array_merge($headers, [
            'User-Agent' => $this->getUserAgent(),
            'Authorization' => $this->token,
        ]);

        if (! is_null($content)) {
            $content = json_encode($content);

            $headers['Content-Type'] = 'application/json';
            $headers['Content-Length'] = strlen($content);
        } else {
            $content = '';
        }

        $fullUrl = self::BASE_URL.'/'.$url;

        $sendRequest = function () use ($method, $fullUrl, $content, $headers, $deferred) {
            $this->driver->runRequest($method, $fullUrl, $content, $headers)->done(function (ResponseInterface $response) use ($fullUrl, $deferred) {
                // ...
            });
        };

        $sendRequest();

        return $deferred->promise();
    }

    /**
     * Returns an exception based on the request.
     *
     * @param ResponseInterface $response
     *
     * @return Throwable
     */
    public function handleError(ResponseInterface $response): Throwable
    {
        switch ($response->getStatusCode()) {
            case 400:
                return new DiscordRequestFailedException($response->getReasonPhrase());
            case 401:
                return new InvalidTokenException($response->getReasonPhrase());
            case 403:
                return new NoPermissionsException($response->getReasonPhrase());
            case 404:
                return new NotFoundException($response->getReasonPhrase());
            case 500:
                if (contains(strtolower((string) $response->getBody()), ['longer than 2000 characters', 'string value is too long'])) {
                    // Response was longer than 2000 characters and was blocked by Discord.
                    return new ContentTooLongException('Response was more than 2000 characters. Use another method to get this data.');
                }
            default:
                return new DiscordRequestFailedException($response->getReasonPhrase());
        }
    }

    /**
     * Returns the User-Agent of the HTTP client.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return 'DiscordBot (https://github.com/teamreflex/DiscordPHP, '.Discord::VERSION.')';
    }
}
