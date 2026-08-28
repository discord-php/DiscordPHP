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

namespace Discord\Parts\Gateway;

use Discord\Parts\OAuth\Application;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * The ready event is dispatched when a client has completed the initial handshake with the gateway (for new sessions).
 *
 * The ready event can be the largest and most complex event the gateway will send, as it contains all the state required for a client to begin interacting with the rest of the platform.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#ready
 *
 * @since 10.56.11
 *
 * @property int              $v                  API version.
 * @property User             $user               Information about the user including email.
 * @property array            $guilds             Guilds the user is in.
 * @property string           $session_id         Used for resuming connections.
 * @property string           $resume_gateway_url Gateway URL for resuming connections.
 * @property ?array|null      $shard              Shard information associated with this session, if sent when identifying.
 * @property Application|null $application        Contains `id`, `flags`, and `flags_new`.
 */
class Ready extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'v',
        'user',
        'guilds',
        'session_id',
        'resume_gateway_url',
        'shard',
        'application',
    ];

    public function getUserAttribute(): User
    {
        return $this->attributePartHelper('user', User::class);
    }

    public function getApplicationAttribute(): ?Application
    {
        return $this->attributePartHelper('application', Application::class);
    }
}
