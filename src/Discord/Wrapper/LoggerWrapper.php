<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Wrapper;

use Monolog\Logger as Monolog;

/**
 * Provides an easy to use wrapper for the logger.
 */
class LoggerWrapper
{
    /**
     * The monolog logger.
     *
     * @var Monolog Logger.
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
     * @param Monolog $logger  The Monolog logger.
     * @param bool    $enabled Whether logging is enabled.
     */
    public function __construct(Monolog $logger, $enabled = true)
    {
        $this->logger  = $logger;
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
    public function __call($function, $params)
    {
        if (! $this->enabled) {
            return false;
        }

        return call_user_func_array([$this->logger, $function], $params);
    }
}
