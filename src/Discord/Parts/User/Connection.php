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

namespace Discord\Parts\User;

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;
use Discord\Parts\Guild\Integration;

/**
 * The connection object that a user has attached.
 *
 * @link https://discord.com/developers/docs/resources/user#connection-object
 *
 * @since 10.33.0
 *
 * @property string     $id            The id of the connection account.
 * @property string     $name          The username of the connection account.
 * @property string     $type          The service of this connection.
 * @property bool|null  $revoked       Whether the connection is revoked.
 * @property array|null $integrations  An array of partial server integrations.
 * @property bool       $verified      Whether the connection is verified.
 * @property bool       $friend_sync   Whether friend sync is enabled for this connection.
 * @property bool       $show_activity Whether activities related to this connection will be shown in presence updates.
 * @property bool       $two_way_link  Whether this connection has a corresponding third party OAuth2 token.
 * @property int        $visibility    Visibility of this connection.
 */
class Connection extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'name',
        'type',
        'revoked',
        'integrations',
        'verified',
        'friend_sync',
        'show_activity',
        'two_way_link',
        'visibility',
    ];

    /**
     * Gets the integrations attribute.
     *
     * @return ExCollectionInterface<Integration>
     */
    protected function getIntegrationsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('integrations', Integration::class);
    }
}
