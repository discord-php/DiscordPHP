<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Carbon\Carbon;
use Discord\Http\Endpoint;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\ExtendedPromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A Guild Template is a code that when used, creates a guild based on a
 * snapshot of an existing guild.
 *
 * @link https://discord.com/developers/docs/resources/guild-template
 *
 * @since 7.0.0
 *
 * @property      string     $code                    The template code (unique ID).
 * @property      string     $name                    Template name.
 * @property      ?string    $description             The description for the template. Up to 120 characters.
 * @property      int        $usage_count             Number of times this template has been used.
 * @property      string     $creator_id              The ID of the user who created the template.
 * @property      User       $creator                 The user who created the template.
 * @property      Carbon     $created_at              A timestamp of when the template was created.
 * @property      Carbon     $updated_at              When this template was last synced to the source guild.
 * @property      string     $source_guild_id         The ID of the guild this template is based on.
 * @property-read Guild|null $source_guild            The guild this template is based on.
 * @property      object     $serialized_source_guild The guild snapshot this template contains.
 * @property      ?bool      $is_dirty                Whether the template has unsynced changes.
 */
class GuildTemplate extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'usage_count',
        'creator_id',
        'creator',
        'created_at',
        'updated_at',
        'source_guild_id',
        'serialized_source_guild',
        'is_dirty',
    ];

    /**
     * Returns the id attribute.
     *
     * @return string The id attribute.
     */
    protected function getIdAttribute(): string
    {
        return $this->code;
    }

    /**
     * Returns the source guild attribute.
     *
     * @return Guild The guild snapshot this template contains.
     */
    protected function getSourceGuildAttribute(): Guild
    {
        if ($guild = $this->discord->guilds->get('id', $this->source_guild_id)) {
            return $guild;
        }

        return $this->createOf(Guild::class, $this->attributes['serialized_source_guild']);
    }

    /**
     * Gets the user that created the template.
     *
     * @return User
     */
    protected function getCreatorAttribute(): User
    {
        if ($creator = $this->discord->users->get('id', $this->creator_id)) {
            return $creator;
        }

        return $this->factory->part(User::class, (array) $this->attributes['creator'], true);
    }

    /**
     * Returns the created at attribute.
     *
     * @return Carbon The time that the guild template was created.
     *
     * @throws \Exception
     */
    protected function getCreatedAtAttribute(): Carbon
    {
        return new Carbon($this->attributes['created_at']);
    }

    /**
     * Returns the updated at attribute.
     *
     * @return Carbon The time that the guild template was updated.
     *
     * @throws \Exception
     */
    protected function getUpdatedAtAttribute(): Carbon
    {
        return new Carbon($this->attributes['updated_at']);
    }

    /**
     * Creates a guild from this template. Can be used only by bots in less than
     * 10 guilds.
     *
     * @link https://discord.com/developers/docs/resources/guild-template#create-guild-from-guild-template
     *
     * @param array       $options         An array of options.
     * @param string      $options['name'] The name of the guild (2-100 characters).
     * @param string|null $options['icon'] The base64 128x128 image for the guild icon.
     *
     * @return ExtendedPromiseInterface<Guild>
     */
    public function createGuild($options = []): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setRequired('name')
            ->setDefined([
                'name',
                'icon',
            ])
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('icon', 'string');

        $options = $resolver->resolve($options);

        $roles = $channels = [];
        if (isset($this->attributes['is_dirty']) && ! $this->is_dirty) {
            $roles = $this->attributes['serialized_source_guild']->roles;
            $channels = $this->attributes['serialized_source_guild']->channels;
        }

        return $this->http->post(Endpoint::bind(Endpoint::GUILDS_TEMPLATE, $this->code), $options)
            ->then(function ($response) use ($roles, $channels) {
                /** @var ?Guild */
                if (! $guildPart = $this->discord->guilds->get('id', $response->id)) {
                    /** @var Guild */
                    $guildPart = $this->discord->guilds->create((array) $response + ['roles' => $roles], true);

                    foreach ($channels as $channel) {
                        $guildPart->channels->pushItem($guildPart->channels->create($channel, true));
                    }

                    $this->discord->guilds->pushItem($guildPart);
                }

                return $guildPart;
            });
    }

    /**
     * Returns the template URL.
     *
     * @return string The URL to the guild template.
     */
    public function __toString(): string
    {
        return 'https://discord.new/'.$this->code;
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/guild-template#create-guild-template-json-params
     */
    public function getCreatableAttributes(): array
    {
        return [
            'name' => $this->name,
        ] + $this->makeOptionalAttributes([
            'description' => $this->description,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/guild-template#modify-guild-template-json-params
     */
    public function getUpdatableAttributes(): array
    {
        return $this->makeOptionalAttributes([
            'name' => $this->name,
            'description' => $this->description,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'template_code' => $this->code,
        ];
    }
}
