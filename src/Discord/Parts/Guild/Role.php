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

namespace Discord\Parts\Guild;

use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Part;
use Discord\Parts\Permissions\RolePermission;
use Discord\Repository\Guild\RoleRepository;
use React\Promise\PromiseInterface;
use Stringable;

use function React\Promise\reject;

/**
 * A role defines permissions for the guild. Members can be added to the role.
 * The role belongs to a guild.
 *
 * @link https://discord.com/developers/docs/topics/permissions#role-object
 *
 * @since 2.0.0
 *
 * @property      string         $id            The unique identifier of the role.
 * @property      string         $name          The name of the role.
 * @property      int            $color         (deprecated) Integer representation of hexadecimal color code.
 * @property      Colors         $colors        The role's colors.
 * @property      bool           $hoist         If this role is pinned in the user listing
 * @property      ?string|null   $icon          The URL to the role icon.
 * @property-read string|null    $icon_hash     The icon hash for the role.
 * @property      ?string|null   $unicode_emoji The unicode emoji for the role.
 * @property      int            $position      Position of this role (roles with the same position are sorted by id).
 * @property      RolePermission $permissions   Permission bit set.
 * @property      bool           $managed       Whether this role is managed by an integration.
 * @property      bool           $mentionable   Whether the role is mentionable.
 * @property      RoleTags|null  $tags          The tags this role has (`bot_id`, `integration_id`, `premium_subscriber`, `subscription_listing_id`, `available_for_purchase`, and `guild_connections`).
 * @property      int            $flags         Role flags combined as a bitfield.
 *
 * @property      string|null $guild_id The unique identifier of the guild that the role belongs to.
 * @property-read Guild|null  $guild    The guild that the role belongs to.
 */
class Role extends Part implements Stringable
{
    /** Role can be selected by members in an onboarding prompt. */
    public const FLAG_IN_PROMPT = 1 << 0;
    /** @deprecated 10.36.32 use `Role::FLAG_IN_PROMPT` */
    public const IN_PROMPT = self::FLAG_IN_PROMPT;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'name',
        'color',
        'colors',
        'hoist',
        'icon',
        'unicode_emoji',
        'position',
        'permissions',
        'managed',
        'mentionable',
        'tags',
        'flags',

        // @internal
        'guild_id',
    ];

    /**
     * Compares this role to another role to determine relative ordering.
     *
     * Ordering rules:
     * - Primary: ascending by position (lower position comes first).
     * - Tiebreaker: descending by ID (higher ID comes first when positions are equal).
     *
     * Returns:
     * - -1 if this role should come before the given role,
     * -  0 if both roles are considered equal in ordering,
     * -  1 if this role should come after the given role.
     *
     * @param Role $role The role to compare against.
     *
     * @return int Comparison result suitable for use with sorting functions.
     *
     * @since 10.40.0
     */
    public function comparePosition($role): int
    {
        if ($this->position === $role->position) {
            return $role->id <=> $this->id;
        }

        return $this->position <=> $role->position;
    }

    /**
     * Gets the colors attribute.
     *
     * @return Colors The role's colors.
     *
     * @since 10.18.1
     */
    protected function getColorsAttribute(): Colors
    {
        return $this->createOf(Colors::class, $this->attributes['colors']);
    }

    /**
     * Gets the permissions attribute.
     *
     * @return RolePermission The role permission.
     *
     * @since 10.0.0 Replaced setPermissionsAttribute() to save up memory.
     */
    protected function getPermissionsAttribute(): RolePermission
    {
        return $this->createOf(RolePermission::class, ['bitwise' => $this->attributes['permissions']]);
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild|null The guild attribute.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Sets the color for a role. RGB.
     *
     * @param int $red   The red value in RGB.
     * @param int $green The green value in RGB.
     * @param int $blue  The blue value in RGB.
     *
     * @deprecated 10.18.2 Use `Role::setColors()`. Color will still be returned by the API, but using the colors field is recommended when doing requests.
     */
    public function setColor(int $red = 0, int $green = 0, int $blue = 0): void
    {
        $this->color = ($red * 16 ** 4 + $green * 16 ** 2 + $blue);
    }

    /**
     * Sets the colors for a role.
     *
     * Roles without colors (Role::colors->primary_color === 0) do not count towards the final computed color in the user list.
     *
     * When sending tertiary_color the API enforces the role color to be a holographic style with values of:
     * primary_color = 11127295, secondary_color = 16759788, and tertiary_color = 16761760.
     *
     * @param int      $primary   The primary color for the role.
     * @param int|null $secondary The secondary color for the role, this will make the role a gradient between the other provided colors.
     * @param int|null $tertiary  The tertiary color for the role, this will turn the gradient into a holographic style.
     *
     * @since 10.18.2
     */
    public function setColors(int $primary = 0, ?int $secondary = null, ?int $tertiary = null): void
    {
        $colors = ['primary_color' => $primary];
        if ($secondary !== null) {
            $colors['secondary_color'] = $secondary;
        }
        if ($tertiary !== null) {
            $colors['tertiary_color'] = $tertiary;
        }

        $this->color = $primary;
        $this->colors = $this->createOf(Colors::class, $colors);
    }

    /**
     * Returns the role icon.
     *
     * @param string $format The image format.
     * @param int    $size   The size of the image.
     *
     * @return string|null The URL to the role icon or null.
     */
    public function getIconAttribute(string $format = 'png', int $size = 64): ?string
    {
        if (! isset($this->attributes['icon'])) {
            return null;
        }

        static $allowed = ['png', 'jpg', 'webp'];

        if (! in_array(strtolower($format), $allowed)) {
            $format = 'png';
        }

        return "https://cdn.discordapp.com/role-icons/{$this->id}/{$this->attributes['icon']}.{$format}?size={$size}";
    }

    /**
     * Returns the role icon hash.
     *
     * @return string|null The role icon hash or null.
     */
    protected function getIconHashAttribute(): ?string
    {
        return $this->attributes['icon'] ?? null;
    }

    /**
     * Gets the role's tags.
     *
     * @return RoleTags|null The role's tags or null.
     *
     * @since 10.19.0
     */
    protected function getTagsAttribute(): ?RoleTags
    {
        return $this->attributePartHelper('tags', RoleTags::class);
    }

    /**
     * @inheritDoc
     *
     * @link https://discord.com/developers/docs/resources/guild#create-guild-role-json-params
     */
    public function getCreatableAttributes(): array
    {
        return $this->makeOptionalAttributes([
            'name' => $this->name,
            'permissions' => (string) $this->getPermissionsAttribute(),
            'color' => $this->color,
            'hoist' => $this->hoist,
            'icon' => $this->getIconHashAttribute(),
            'unicode_emoji' => $this->unicode_emoji,
            'mentionable' => $this->mentionable,
        ]);
    }

    /**
     * @inheritDoc
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-role-json-params
     */
    public function getUpdatableAttributes(): array
    {
        return $this->makeOptionalAttributes([
            'name' => $this->name,
            'permissions' => (string) $this->getPermissionsAttribute(),
            'color' => $this->color,
            'hoist' => $this->hoist,
            'icon' => $this->getIconHashAttribute(),
            'unicode_emoji' => $this->unicode_emoji,
            'mentionable' => $this->mentionable,
        ]);
    }

    /**
     * Gets the originating repository of the part.
     *
     * @since 10.42.0
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return RoleRepository|null The repository, or null if required part data is missing.
     */
    public function getRepository(): RoleRepository|null
    {
        if (! isset($this->attributes['guild_id'])) {
            return null;
        }

        /** @var Guild $guild */
        $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);

        return $guild->roles;
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
                if (! $botperms->manage_roles) {
                    return reject(new NoPermissionsException("The bot does not have permission to manage roles in guild {$this->guild_id}."));
                }
            }

            if ($botHighestRole = $guild->roles->getCurrentMemberHighestRole()) {
                if ($botHighestRole->comparePosition($this) <= 0) {
                    return reject(new NoPermissionsException("The bot's highest role is not higher than the role {$this->id} in guild {$this->guild_id}."));
                }
            }

            return $guild->roles->save($this, $reason);
        }

        return parent::save();
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'role_id' => $this->id,
        ];
    }

    /**
     * Returns a formatted mention.
     *
     * @return string A formatted mention.
     */
    public function __toString(): string
    {
        return "<@&{$this->id}>";
    }
}
