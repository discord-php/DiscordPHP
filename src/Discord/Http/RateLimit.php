<?php

namespace Discord\Http;

/**
 * Represents a rate-limit given by Discord.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class RateLimit
{
    /**
     * Whether the rate-limit is global.
     *
     * @var boolean
     */
    protected $global;

    /**
     * Time in seconds of when to retry after.
     *
     * @var float
     */
    protected $retry_after;

    /**
     * Rate limit constructor.
     *
     * @param bool $global
     * @param float $retry_after
     */
    public function __construct(bool $global, float $retry_after)
    {
        $this->global = $global;
        $this->retry_after = $retry_after;
    }

    /**
     * Gets the global parameter.
     *
     * @return boolean
     */
    public function isGlobal(): bool
    {
        return $this->global;
    }

    /**
     * Gets the retry after parameter.
     *
     * @return float
     */
    public function getRetryAfter(): float
    {
        return $this->retry_after;
    }

    /**
     * Converts a rate-limit to a user-readable string.
     *
     * @return string
     */
    public function __toString()
    {
        return ($this->global ? 'Global' : 'Non-global').' rate-limit, retry after '.$this->retry_after.' ms';
    }
}
