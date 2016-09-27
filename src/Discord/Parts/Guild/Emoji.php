<?php

namespace Discord\Parts\Guild;

use Discord\Helpers\Collection;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

/**
 * An emoji object represents a custom emoji.
 *
 * @property string                     $id             The identifier for the emoji.
 * @property string                     $name           The name of the emoji.
 * @property \Discord\Parts\Guild\Guild $guild          The guild that owns the emoji.
 * @property string                     $guild_id       The identifier of the guild that owns the emoji.
 * @property bool                       $managed        Whether this emoji is managed by a role.
 * @property bool                       $require_colons Whether the emoji requires colons to be triggered.
 * @property Collection[Role]           $roles          The roles that are allowed to use the emoji.
 */
class Emoji extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'name', 'guild_id', 'managed', 'require_colons', 'roles'];

    /**
     * Returns the guild attribute.
     *
     * @return Guild The guild the emoji belongs to.
     */
    public function getGuildAttribute()
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the roles attribute.
     *
     * @return Collection A collection of roles for the emoji.
     */
    public function getRolesAttribute()
    {
        return $this->guild->roles->filter(function ($role) {
            return (array_search($role->id, $this->attributes['roles']) !== false);
        });
    }
}