<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Discord\Helpers\ExCollectionInterface;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use Discord\Repository\EmojiRepository;
use Discord\Repository\Guild\EmojiRepository as GuildEmojiRepository;
use React\Promise\PromiseInterface;
use Stringable;

use function React\Promise\reject;

/**
 * An emoji object represents a custom emoji.
 *
 * @link https://discord.com/developers/docs/resources/emoji
 *
 * @since 4.0.2
 *
 * @property ?string|null                       $id             The identifier for the emoji.
 * @property ?string|null                       $name           The name of the emoji (can be null only in reaction emoji objects).
 * @property ExCollectionInterface<Role>|Role[] $roles          The roles that are all owed to use the emoji.
 * @property User|null                          $user           User that created this emoji.
 * @property bool|null                          $require_colons Whether the emoji requires colons to be triggered.
 * @property bool|null                          $managed        Whether this emoji is managed by a role.
 * @property bool|null                          $animated       Whether the emoji is animated.
 * @property bool|null                          $available      Whether this emoji can be used, may be false due to loss of Server Boosts.
 *
 * @property      string|null $guild_id The identifier of the guild that owns the emoji.
 * @property-read Guild|null  $guild    The guild that owns the emoji.
 */
class Emoji extends Part implements Stringable
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'name',
        'roles',
        'user',
        'require_colons',
        'managed',
        'animated',
        'available',

        // @internal
        'guild_id',
    ];

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild the emoji belongs to.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the roles attribute.
     *
     * @return ExCollectionInterface<Role>|Role[] A collection of roles for the emoji.
     */
    protected function getRolesAttribute(): ExCollectionInterface
    {
        /** @var ExCollectionInterface $roles */
        $roles = new ($this->discord->getCollectionClass());

        if (empty($this->attributes['roles'])) {
            return $roles;
        }

        $roles->fill(array_fill_keys($this->attributes['roles'], null));

        if ($guild = $this->guild) {
            $roles->merge($guild->roles->filter(fn ($role) => in_array($role->id, $this->attributes['roles'])));
        }

        return $roles;
    }

    /**
     * Gets the user that created the emoji.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        if (! isset($this->attributes['user'])) {
            return null;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->attributePartHelper('user', User::class);
    }

    /**
     * Converts the emoji to the format required for creating a reaction.
     *
     * @return string
     */
    public function toReactionString(): string
    {
        if ($this->id) {
            return ($this->animated ? 'a' : '').":{$this->name}:{$this->id}";
        }

        return $this->name;
    }

    /**
     * Converts the emoji to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->id) {
            return '<'.($this->animated ? 'a:' : ':')."{$this->name}:{$this->id}>";
        }

        return $this->name;
    }

    /**
     * @inheritDoc
     *
     * @link https://discord.com/developers/docs/resources/emoji#modify-guild-emoji-json-params
     */
    public function getUpdatableAttributes(): array
    {
        return $this->makeOptionalAttributes([
            'name' => $this->name,
            'roles' => $this->attributes['roles'] ?? null,
        ]);
    }

    /**
     * Gets the originating repository of the part.
     *
     * @since 10.42.0
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return EmojiRepository|GuildEmojiRepository The repository.
     */
    public function getRepository(): EmojiRepository|GuildEmojiRepository
    {
        if (isset($this->attributes['guild_id'])) {
            /** @var Guild $guild */
            $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);

            return $guild->emojis;
        }

        return $this->discord->emojis;
    }

    /**
     * @inheritDoc
     */
    public function save(?string $reason = null): PromiseInterface
    {
        if (isset($this->attributes['guild_id'])) {
            /** @var Guild $guild */
            $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);
            if ($botperms = $guild->getBotPermissions()) {
                if ($this->created) {
                    if (! $botperms->create_guild_expressions) {
                        return reject(new NoPermissionsException("You do not have permission to create emojis in the guild {$guild->id}."));
                    }
                } elseif (! $botperms->manage_guild_expressions) {
                    return reject(new NoPermissionsException("You do not have permission to manage emojis in the guild {$guild->id}."));
                } elseif ($this->user->id === $this->discord->id && ! $botperms->create_guild_expressions) {
                    return reject(new NoPermissionsException("You do not have permission to create or manage emojis in the guild {$guild->id}."));
                }
            }
        }

        return $this->getRepository()->save($this, $reason);
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'emoji_id' => $this->id,
        ];
    }
}
