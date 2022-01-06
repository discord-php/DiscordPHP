<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Exceptions\InvalidOverwriteException;
use Discord\Http\Endpoint;
use Discord\Parts\Guild\Role;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Command\Overwrite;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

/**
 * Contains application guild commands.
 *
 * @see \Discord\Parts\Interactions\Command\Command
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Command|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Command|null first()                     Returns the first element of the collection.
 * @method Command|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Command|null find(callable $callback)    Runs a filter callback over the repository.
 */
class GuildCommandRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_APPLICATION_COMMANDS,
        'get' => Endpoint::GUILD_APPLICATION_COMMAND,
        'create' => Endpoint::GUILD_APPLICATION_COMMANDS,
        'update' => Endpoint::GUILD_APPLICATION_COMMAND,
        'delete' => Endpoint::GUILD_APPLICATION_COMMAND,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Command::class;

    /**
     * Sets overwrite to all application commands in the guild.
     *
     * @param Part      $part      A role or user.
     * @param Overwrite $overwrite An overwrite object.
     *
     * @return ExtendedPromiseInterface
     */
    public function setOverwrite(Part $part, Overwrite $overwrite): ExtendedPromiseInterface
    {
        if (!($part instanceof Role || $part instanceof User || $part instanceof Member)) {
            return \React\Promise\reject(new InvalidOverwriteException('Given part was not one of role or user.'));
        }

        return $this->http->put(Endpoint::bind(Endpoint::GUILD_APPLICATION_COMMAND_PERMISSIONS, $this->vars['application_id'], $this->vars['guild_']), $overwrite->getRawAttributes());
    }
}
