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

namespace Discord\Parts\User;

use Discord\Parts\Part;
use Discord\Parts\OAuth\ApplicationRoleConnectionMetadata;

/**
 * The role connection object that an application has attached to a user.
 *
 * @link https://discord.com/developers/docs/resources/user#application-role-connection-object
 *
 * @since 10.33.0
 *
 * @property ?string                           $platform_name     The vanity name of the platform a bot has connected (max 50 characters).
 * @property ?string                           $platform_username The username on the platform a bot has connected (max 100 characters).
 * @property ApplicationRoleConnectionMetadata $metadata          Object mapping application role connection metadata keys to their string-ified value (max 100 characters) for the user on the platform a bot has connected.
 */
class ApplicationRoleConnection extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'platform_name',
        'platform_username',
        'metadata',
    ];

    /**
     * Gets the metadata attribute.
     *
     * @return ApplicationRoleConnectionMetadata
     */
    protected function getMetadataAttribute(): ApplicationRoleConnectionMetadata
    {
        return $this->attributePartHelper('metadata', ApplicationRoleConnectionMetadata::class);
    }
}
