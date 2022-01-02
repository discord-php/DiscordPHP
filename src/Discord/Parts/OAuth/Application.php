<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\OAuth;

use Discord\Parts\Part;
use Discord\Parts\Permissions\Permission;
use Discord\Parts\User\User;

/**
 * The OAuth2 application of the bot.
 *
 * @property string   $id          The client ID of the OAuth application.
 * @property string   $name        The name of the OAuth application.
 * @property string   $description The description of the OAuth application.
 * @property string   $icon        The icon hash of the OAuth application.
 * @property string   $invite_url  The invite URL to invite the bot to a guild.
 * @property string[] $rpc_origins An array of RPC origin URLs.
 * @property int      $flags       ?
 * @property User     $owner       The owner of the OAuth application.
 */
class Application extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'name', 'description', 'icon', 'rpc_origins', 'flags', 'owner'];

    /**
     * Returns the owner of the application.
     *
     * @return User       Owner of the application.
     * @throws \Exception
     */
    protected function getOwnerAttribute(): ?User
    {
        if (isset($this->attributes['owner'])) {
            return $this->factory->create(User::class, $this->attributes['owner'], true);
        }
        
        return null;
    }

    /**
     * Returns the invite URL for the application.
     *
     * @param Permission|int $permissions Permissions to set.
     *
     * @return string Invite URL.
     */
    public function getInviteURLAttribute($permissions = 0): string
    {
        if ($permissions instanceof Permission) {
            $permissions = $permissions->bitwise;
        }

        return "https://discordapp.com/oauth2/authorize?client_id={$this->id}&scope=bot&permissions={$permissions}";
    }
}
