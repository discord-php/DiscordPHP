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
use Discord\Repository\Interaction\GlobalCommandRepository;

/**
 * The OAuth2 application of the bot.
 *
 * @property string                  $id                     The client ID of the OAuth application.
 * @property string                  $name                   The name of the OAuth application.
 * @property string                  $icon                   The icon hash of the OAuth application.
 * @property string                  $description            The description of the OAuth application.
 * @property string[]                $rpc_origins            An array of RPC origin URLs.
 * @property bool                    $bot_public             When false only app owner can join the app's bot to guilds.
 * @property bool                    $bot_require_code_grant When true the app's bot will only join upon completion of the full oauth2 code grant flow.
 * @property string|null             $terms_of_service_url   The url of the app's terms of service.
 * @property string|null             $privacy_policy_url     The url of the app's privacy policy
 * @property User|null               $owner                  The owner of the OAuth application.
 * @property string                  $verify_key             The hex encoded key for verification in interactions and the GameSDK's GetTicket.
 * @property object|null             $team                   If the application belongs to a team, this will be a list of the members of that team.
 * @property int                     $flags                  The application's public flags.
 * @property string                  $invite_url             The invite URL to invite the bot to a guild.
 * @property GlobalCommandRepository $commands               The application global commands.
 */
class Application extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
        'name',
        'icon',
        'description',
        'rpc_origins',
        'bot_public',
        'bot_require_code_grant',
        'terms_of_service_url',
        'privacy_policy_url',
        'owner',
        'verify_key',
        'team',
        'flags',
    ];

    /**
     * @inheritdoc
     */
    protected $repositories = [
        'commands' => GlobalCommandRepository::class,
    ];

    /**
     * Returns the owner of the application.
     *
     * @return User|null Owner of the application.
     */
    protected function getOwnerAttribute(): ?User
    {
        if (! isset($this->attributes['owner'])) {
            return null;
        }

        if ($owner = $this->discord->users->get('id', $this->attributes['owner']->id)) {
            return $owner;
        }

        return $this->factory->part(User::class, (array) $this->attributes['owner'], true);
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

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'application_id' => $this->id,
        ];
    }
}
