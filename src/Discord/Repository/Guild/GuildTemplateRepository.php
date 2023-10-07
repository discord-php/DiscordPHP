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

use Discord\Http\Endpoint;
use Discord\Parts\Guild\GuildTemplate;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

/**
 * Contains guild templates of a guild.
 *
 * @see GuildTemplate
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 7.0.0
 *
 * @method GuildTemplate|null get(string $discrim, $key)
 * @method GuildTemplate|null pull(string|int $key, $default = null)
 * @method GuildTemplate|null first()
 * @method GuildTemplate|null last()
 * @method GuildTemplate|null find(callable $callback)
 */
class GuildTemplateRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $discrim = 'code';

    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_TEMPLATES,
        'get' => Endpoint::GUILDS_TEMPLATE,
        'create' => Endpoint::GUILD_TEMPLATES,
        'update' => Endpoint::GUILD_TEMPLATE,
        'delete' => Endpoint::GUILD_TEMPLATE,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = GuildTemplate::class;

    /**
     * Syncs the template to the guild's current state. Requires the MANAGE_GUILD permission.
     *
     * @param string $template_code The guild template code.
     *
     * @return ExtendedPromiseInterface
     */
    public function sync(string $template_code): ExtendedPromiseInterface
    {
        return $this->http->put(Endpoint::bind(Endpoint::GUILD_TEMPLATE, $this->vars['guild_id'], $template_code))->then(function ($guild_template) use ($template_code) {
            return $this->cache->get($template_code)->then(function ($guildTemplate) use ($guild_template, $template_code) {
                if ($guildTemplate === null) {
                    $guildTemplate = $this->factory->part(GuildTemplate::class, (array) $guild_template, true);
                } else {
                    $guildTemplate->fill($guild_template);
                }

                return $this->cache->set($template_code, $guildTemplate)->then(function ($success) use ($guildTemplate) {
                    return $guildTemplate;
                });
            });
        });
    }
}
