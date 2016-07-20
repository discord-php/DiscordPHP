<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\OAuth;

use Discord\Parts\Part;

/**
 * The OAuth2 application of the bot.
 *
 * @property string $id The client ID of the OAuth application.
 * @property string $name The name of the OAuth application.
 * @property string $description The description of the OAuth application.
 * @property string $icon The icon hash of the OAuth application.
 * @property string $invite_url The invite URL to invite the bot to a guild.
 */
class Application extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'name', 'description', 'icon'];

    /**
     * Returns the invite URL for the application.
     *
     * @param int $permissions Permissions to set.
     *
     * @return string Invite URL.
     */
    public function getInviteURLAttribute($permissions = 0)
    {
        return "https://discordapp.com/oauth2/authorize?client_id={$this->id}&scope=bot&permissions={$permissions}";
    }
}
