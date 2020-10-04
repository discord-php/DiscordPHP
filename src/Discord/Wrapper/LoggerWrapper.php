<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Wrapper;

use Psr\Log\LoggerInterface;

/**
 * Provides an easy to use wrapper for the logger.
 *
 * @method LoggerInterface emergency(string $message, array $context = [])
 * @method LoggerInterface alert(string $message, array $context = [])
 * @method LoggerInterface critical(string $message, array $context = [])
 * @method LoggerInterface error(string $message, array $context = [])
 * @method LoggerInterface warning(string $message, array $context = [])
 * @method LoggerInterface notice(string $message, array $context = [])
 * @method LoggerInterface info(string $message, array $context = [])
 * @method LoggerInterface debug(string $message, array $context = [])
 * @method LoggerInterface log(string $level, string $message, array $context = [])
 */
class LoggerWrapper
{
    /**
     * The logger.
     *
     * @var LoggerInterface Logger.
     */
    protected $logger;

    /**
     * Whether logging is enabled.
     *
     * @var bool Logging enabled.
     */
    protected $enabled;

    /**
     * Constructs the logger.
     *
     * @param LoggerInterface $logger  The Monolog logger.
     * @param bool            $enabled Whether logging is enabled.
     */
    public function __construct(LoggerInterface $logger, bool $enabled = true)
    {
        $this->logger = $logger;
        $this->enabled = $enabled;
    }

    /**
     * Handles dynamic calls to the class.
     *
     * @param string $function The function called.
     * @param array  $params   The paramaters.
     *
     * @return mixed
     */
    public function __call(string $function, array $params)
    {
        if (! $this->enabled) {
            return false;
        }

        return call_user_func_array([$this->logger, $function], $params);
    }
}
