<?php

namespace Discord\Http;

use Discord\Discord;
use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Exceptions\Rest\ContentTooLongException;
use Discord\Exceptions\Rest\NoPermissionsException;
use Discord\Exceptions\Rest\NotFoundException;
use Psr\Http\Message\ResponseInterface;
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
    private string $token;

    /**
     * HTTP driver.
     *
     * @var DriverInterface
     */
    protected DriverInterface $driver;

    /**
     * Http wrapper constructor.
     *
     * @param string $token
     * @param DriverInterface|null $driver
     */
    public function __construct(string $token, DriverInterface $driver = null): void
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
