<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts;

use ArrayAccess;
use Discord\Discord;
use Discord\Factory\Factory;
use Discord\Http\Http;
use JsonSerializable;

/**
 * This class is the base of all objects that are returned. All "Parts" extend
 * off this base class.
 *
 * @since 2.0.0
 */
abstract class Part implements PartInterface, ArrayAccess, JsonSerializable
{
    /**
     * The HTTP client.
     *
     * @var Http Client.
     */
    protected $http;

    /**
     * The factory.
     *
     * @var Factory Factory.
     */
    protected $factory;

    /**
     * The Discord client.
     *
     * @var Discord Client.
     */
    protected $discord;

    /**
     * Custom script data.
     * Used for storing custom information, used by end products.
     *
     * @var mixed
     *
     * @deprecated 10.0.0 Relying on this variable with dynamic caching is discouraged.
     */
    public $scriptData;

    /**
     * The parts fillable attributes.
     *
     * @var array The array of attributes that can be mass-assigned.
     */
    protected $fillable = [];

    /**
     * The parts attributes.
     *
     * @var array The parts attributes and content.
     */
    protected $attributes = [];

    /**
     * Attributes which are visible from debug info.
     *
     * @var array
     */
    protected $visible = [];

    /**
     * Attributes that are hidden from debug info.
     *
     * @var array Attributes that are hidden from public.
     */
    protected $hidden = [];

    /**
     * An array of repositories that can exist in a part.
     *
     * @var array Repositories.
     */
    protected $repositories = [];

    /**
     * An array of repositories.
     *
     * @var array
     */
    protected $repositories_cache = [];

    /**
     * Is the part already created in the Discord servers?
     *
     * @var bool Whether the part has been created.
     */
    public $created = false;

    use PartTrait;
}
