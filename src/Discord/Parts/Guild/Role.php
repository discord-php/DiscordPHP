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
use Discord\Parts\Permissions\RolePermission;
use Stringable;

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
    // Flags
    public const IN_PROMPT = 1 << 0; // Role can be selected by members in an onboarding prompt.

    /**
     * {@inheritDoc}
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
    protected function getPermissionsAttribute(): Part
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
     * @deprecated 10.18.2 Use `Role::setColors()`
     */
    public function setColor(int $red = 0, int $green = 0, int $blue = 0): void
    {
        $this->color = ($red * 16 ** 4 + $green * 16 ** 2 + $blue);
    }

    /**
     * Sets the colors for a role.
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
     * Gets the Role Tag attribute.
     *
     * @return RoleTags|null The role's tags.
     *
     * @since 10.19.0
     */
    protected function getRoleTagAttribute(): ?RoleTags
    {
        if (! isset($this->attributes['tags'])) {
            return null;
        }

        return $this->createOf(RoleTags::class, $this->attributes['tags']);
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
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
     * {@inheritDoc}
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
