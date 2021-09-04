<?php

/*
 * This file was a part of the DiscordPHP-Slash project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license which is
 * bundled with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Command;

/**
 * Choices represents an array of choices that can be given to a command.
 *
 * @author David Cole <david.cole1340@gmail.com>
 *
 * @todo convert to single part
 */
class Choices
{
    /**
     * Array of choices.
     *
     * @var array[]
     */
    private $choices;

    /**
     * Choices constructor.
     *
     * @param array $choices
     */
    public function __construct(array $choices)
    {
        $this->choices = $choices;
    }

    /**
     * Handles dynamic get requests to the class.
     *
     * @param string $name
     *
     * @return string|int|null
     */
    public function __get($name)
    {
        foreach ($this->choices as $choice) {
            if ($choice['name'] == $name) {
                return $choice['value'] ?? null;
            }
        }

        return null;
    }

    /**
     * Returns the info to appear when the class is `var_dump`'d.
     *
     * @return array
     */
    public function __debugInfo()
    {
        $response = [];

        foreach ($this->choices as $choice) {
            $response[$choice['name']] = $choice['value'];
        }

        return $response;
    }
}
