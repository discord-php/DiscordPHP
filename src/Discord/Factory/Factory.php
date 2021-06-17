<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Factory;

use Discord\Discord;
use Discord\Http\Http;
use Discord\Parts\Part;
use Discord\Repository\AbstractRepository;

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
     * @param mixed  $data    Data to create the object.
     * @param bool   $created Whether the object is created (if part).
     *
     * @return Part|AbstractRepository The object.
     * @throws \Exception
     */
    public function create(string $class, $data = [], bool $created = false)
    {
        if (! is_array($data)) {
            $data = (array) $data;
        }

        if (strpos($class, 'Discord\\Parts') !== false) {
            $object = $this->part($class, $data, $created);
        } elseif (strpos($class, 'Discord\\Repository') !== false) {
            $object = $this->repository($class, $data);
        } else {
            throw new \Exception('The class '.$class.' is not a Part or a Repository.');
        }

        return $object;
    }

    /**
     * Creates a part.
     *
     * @param string $class   The class to build.
     * @param array  $data    Data to create the object.
     * @param bool   $created Whether the object is created (if part).
     *
     * @return Part The part.
     */
    public function part(string $class, array $data = [], bool $created = false): Part
    {
        return new $class($this->discord, $data, $created);
    }

    /**
     * Creates a repository.
     *
     * @param string $class The class to build.
     * @param array  $data  Data to create the object.
     *
     * @return AbstractRepository The repository.
     */
    public function repository(string $class, array $data = []): AbstractRepository
    {
        return new $class($this->http, $this, $data);
    }
}
