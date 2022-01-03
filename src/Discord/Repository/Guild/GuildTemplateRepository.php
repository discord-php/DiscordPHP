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
 * Contains guildtemplates to guilds.
 *
 * @see \Discord\Parts\Guild\GuildTemplate
 * @see \Discord\Parts\Guild\Guild
 *
 * @method GuildTemplate|null get(string $discrim, $key)  Gets an item from the collection.
 * @method GuildTemplate|null first()                     Returns the first element of the collection.
 * @method GuildTemplate|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method GuildTemplate|null find(callable $callback)    Runs a filter callback over the repository.
 */
class GuildTemplateRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $discrim = 'code';

    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_TEMPLATES,
        'get' => Endpoint::GUILDS_TEMPLATE,
        'create' => Endpoint::GUILD_TEMPLATES,
        'update' => Endpoint::GUILD_TEMPLATE,
        'delete' => Endpoint::GUILD_TEMPLATE,
    ];

    /**
     * @inheritdoc
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
            if ($this->offsetExists($template_code)) {
                $guild_template = $this->factory->create(GuildTemplate::class, $guild_template, true);
                $this->offsetSet($template_code, $guild_template);
            }

            return $guild_template;
        });
    }
}
