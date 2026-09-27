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

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * Server Guide for a guild.
 *
 * Previously known as GuildHome.
 *
 * @link https://github.com/discord/discord-api-spec/blob/7cba79e03a393456fc904cff470097d3be383bec/specs/openapi_preview.json#L25369
 *
 * @since 10.47.0 OpenAPI Preview
 *
 * @property string                                                   $guild_id           The ID of the guild this new member welcome is for.
 * @property bool                                                     $enabled            Whether the new member welcome experience is enabled.
 * @property WelcomeMessage|null                                      $welcome_message    Welcome message shown to new members of the guild.
 * @property ExCollectionInterface<NewMemberAction>|NewMemberAction[] $new_member_actions Actions shown to new members of the guild (max 5).
 * @property ExCollectionInterface<ResourceChannel>|ResourceChannel[] $resource_channels  Read-only channels that provide resources for new members (max 7).
 *
 * @property-read Guild|null $guild The guild associated with the server guide.
 */
class ServerGuide extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'guild_id',
        'enabled',
        'welcome_message',
        'new_member_actions',
        'resource_channels',
    ];

    /**
     * Gets the welcome message.
     *
     * @return WelcomeMessage|null
     */
    protected function getWelcomeMessageAttribute(): ?WelcomeMessage
    {
        return $this->attributePartHelper('welcome_message', WelcomeMessage::class);
    }

    /**
     * Gets the new member actions.
     *
     * @return ExCollectionInterface<NewMemberAction>
     */
    protected function getNewMemberActionsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('new_member_actions', NewMemberAction::class, 'id', ['guild_id' => $this->guild_id]);
    }

    /**
     * Gets the resource channels.
     *
     * @return ExCollectionInterface<ResourceChannel>
     */
    protected function getResourceChannelsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('resource_channels', ResourceChannel::class, 'channel_id', ['guild_id' => $this->guild_id]);
    }

    /**
     * Gets the guild.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }
}
