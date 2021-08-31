<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license which is
 * bundled with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Command;

use Discord\Discord\Enums\ApplicationCommandOptionType;

/**
 * Options represents an array of options that can be given to a command.
 *
 * @author David Cole <david.cole1340@gmail.com>
 *
 * @todo convert to part
 */
class Option
{
    /**
     * The type of option
     * 
     * @var ApplicationCommandOptionType
     */
    private ApplicationCommandOptionType $type;

    /**
     * 1-32 lowercase character name matching ^[\w-]{1,32}$
     * 
     * @var string
     */
    private $name = '';

    /**
     * 1-100 character description
     * 
     * @var string
     */
    private $description = '';

    /**
     * if the parameter is required or optional--default false
     * 
     * @var boolean
     */
    private $required = false;

    /**
     * Array of choices.
     * 
     * choices for STRING, INTEGER, and NUMBER types for the user to pick from, max 25
     *
     * @var Choices
     */
    private Choices $choices;

    /**
     * Array of options
     * 
     * if the option is a subcommand or subcommand group type, this nested options will be the parameters
     * 
     * @var Option
     */
    private Option $options;

    /**
     * Options constructor.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
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
        foreach ($this->options as $option) {
            if ($option['name'] == $name) {
                return $option ?? null;
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

        foreach ($this->options as $option) {
            $response[$option['name']] = $option;
        }

        return $response;
    }
}
