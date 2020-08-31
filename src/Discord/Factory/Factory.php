<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Factory;

use Discord\Discord;
use Discord\Http\Http;

/**
 * Exposes an interface to build part objects without the other requirements.
 */
class Factory
{
    /**
     * The Discord client.
     *
     * @var Discord Client.
     */
    protected $discord;

    /**
     * The HTTP client.
     *
     * @var Http Client.
     */
    protected $http;

    /**
     * Constructs a factory.
     *
     * @param Discord $discord The Discord client.
     * @param Http    $http    The HTTP client.
     */
    public function __construct(Discord $discord, Http $http)
    {
        $this->discord = $discord;
        $this->http = $http;
    }

    /**
     * Creates an object.
     *
     * @param string $class   The class to build.
     * @param array  $data    Data to create the object.
     * @param bool   $created Whether the object is created (if part).
     *
     * @return mixed The object.
     */
    public function create($class, $data = [], $created = false)
    {
        if (! is_array($data)) {
            $data = (array) $data;
        }

        if (strpos($class, 'Discord\\Parts') !== false) {
            $object = new $class($this, $this->discord, $this->http, $data, $created);
        } elseif (strpos($class, 'Discord\\Repository') !== false) {
            $object = new $class($this->http, $this, $data);
        } else {
            throw new \Exception('The class '.$class.' is not a Part or a Repository.');
        }

        return $object;
    }
}
