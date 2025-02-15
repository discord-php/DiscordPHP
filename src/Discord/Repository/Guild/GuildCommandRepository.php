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

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Http\Endpoint;
use Discord\Parts\Part;
use Discord\Parts\Interactions\Command\Command;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

use function Discord\nowait;

/**
 * Contains application guild commands.
 *
 * @see Command
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 7.0.0
 *
 * @method Command|null get(string $discrim, $key)
 * @method Command|null pull(string|int $key, $default = null)
 * @method Command|null first()
 * @method Command|null last()
 * @method Command|null find(callable $callback)
 */
class GuildCommandRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_APPLICATION_COMMANDS,
        'get' => Endpoint::GUILD_APPLICATION_COMMAND,
        'create' => Endpoint::GUILD_APPLICATION_COMMANDS,
        'update' => Endpoint::GUILD_APPLICATION_COMMAND,
        'delete' => Endpoint::GUILD_APPLICATION_COMMAND,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Command::class;

    /**
     * {@inheritDoc}
     */
    public function __construct(Discord $discord, array $vars = [])
    {
        $vars['application_id'] = $discord->application->id; // For the bot's Application Guild Commands

        parent::__construct($discord, $vars);
    }

    /**
     * Attempts to save a part to the Discord servers.
     *
     * @param CommandBuilder|Part $part   The CommandBuilder or part to save.
     * @param string|null         $reason Reason for Audit Log (if supported).
     *
     * @return ExtendedPromiseInterface<Part>
     *
     * @throws \Exception
     */
    public function save(CommandBuilder|Part $part, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($part instanceof CommandBuilder) {
            $method = 'post';
            $endpoint = new Endpoint($this->endpoints['create']);
            $endpoint->bindAssoc($this->vars);
            $attributes = $part->toArray();
        } else {
            if ($part->created) {
                $method = 'patch';
                $endpoint = new Endpoint($this->endpoints['update']);
                $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));
                $attributes = $part->getUpdatableAttributes();
            } else {
                $method = 'post';
                $endpoint = new Endpoint($this->endpoints['create']);
                $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));
                $attributes = $part->getCreatableAttributes();
            }
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->{$method}($endpoint, $attributes, $headers)->then(function ($response) use ($method, $part) {
            switch ($method) {
                case 'patch': // Update old part
                    if ($part instanceof CommandBuilder) {
                        $part = nowait($this->cache->get($part->{$this->discrim})) ?? $this->factory->create($this->class, (array) $response, true);
                    }
                    $part->created = true;
                    $part->fill((array) $response);
                    return $this->cache->set($part->{$this->discrim}, $part)->then(fn ($success) => $part);
                default: // Create new part
                    $newPart = $this->factory->create($this->class, (array) $response, true);
                    return $this->cache->set($newPart->{$this->discrim}, $this->factory->create($this->class, (array) $response, true))->then(fn ($success) => $newPart);
            }
        });
    }
}
