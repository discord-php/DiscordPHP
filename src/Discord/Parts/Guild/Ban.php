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

use Discord\Parts\Part;
use Discord\Parts\User\User;
use Discord\Repository\Guild\BanRepository;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * A Ban is a ban on a user specific to a guild. It is also IP based.
 *
 * @link https://discord.com/developers/docs/resources/guild#ban-object
 *
 * @since 2.0.0
 *
 * @property string $reason  The reason for the ban.
 * @property User   $user    The banned user.
 * @property string $user_id
 *
 * @property      string|null $guild_id
 * @property-read Guild|null  $guild
 */
class Ban extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'reason',
        'user',

        // events
        'guild_id',

        // @internal
        'user_id',
    ];

    /**
     * Returns the user id of the ban.
     *
     * @return string|null
     */
    protected function getUserIdAttribute(): ?string
    {
        if (isset($this->attributes['user_id'])) {
            return $this->attributes['user_id'];
        }

        if (isset($this->attributes['user']->id)) {
            return $this->attributes['user']->id;
        }

        return null;
    }

    /**
     * Returns the guild attribute of the ban.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the user attribute of the ban.
     *
     * @return User
     */
    protected function getUserAttribute(): User
    {
        if ($user = $this->discord->users->get('id', $this->user_id)) {
            return $user;
        }

        return $this->attributePartHelper('user', User::class);
    }

    /**
     * Gets the originating repository of the part.
     *
     * @since 10.42.0
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return BanRepository|null The repository, or null if required part data is missing.
     */
    public function getRepository(): BanRepository|null
    {
        if (! isset($this->attributes['guild_id'])) {
            return null;
        }

        /** @var Guild $guild */
        $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);

        return $guild->bans;
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
                if (! $botperms->ban_members) {
                    return reject(new \DomainException("You do not have permission to ban members in the guild {$guild->id}."));
                }
            }

            return $guild->bans->save($this, $reason);
        }

        return parent::save();
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'user_id' => $this->user_id,
        ];
    }
}
