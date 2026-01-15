<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\AuditLog\AuditLog;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;

/**
 * Contains the audit log of a guild.
 *
 * @see \Discord\Parts\Guild\AuditLog\AuditLog
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 10.36.0
 *
 * @method AuditLog|null get(string $discrim, $key)
 * @method AuditLog|null pull(string|int $key, $default = null)
 * @method AuditLog|null first()
 * @method AuditLog|null last()
 * @method AuditLog|null find(callable $callback)
 */
class AuditLogRepository extends AbstractRepository
{
    /**
     * The discriminator.
     *
     * @var string Discriminator.
     */
    protected $discrim = ''; // None, there can only be one

    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::AUDIT_LOG,
    ];

    /**
     * @inheritDoc
     */
    protected $class = AuditLog::class;

    /**
     * @param object $response
     *
     * @return PromiseInterface<static>
     */
    protected function cacheFreshen($response): PromiseInterface
    {
        return $this->cache->set('', $this->factory->part($this->class, array_merge($this->vars, (array) $response), true))->then(fn ($success) => $this);
    }
}
