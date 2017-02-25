<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildDelete extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $guildPart = $this->factory->create(Guild::class, $data, true);

        if ($this->discord->guilds->has($guildPart->id)) {
            $repositories = [
                'DiscordRepositoryGuildBanRepository',
                'DiscordRepositoryGuildChannelRepository',
                'DiscordRepositoryGuildEmojiRepository',
                'DiscordRepositoryGuildInviteRepository',
                'DiscordRepositoryGuildMemberRepository',
                'DiscordRepositoryGuildRoleRepository',
                'DiscordRepositoryGuildBanRepository',
            ];

            foreach ($repositories as $repository) {
                if ($this->discord->repositories->has($repository)) {
                    $repository = $this->discord->repositories->offsetGet($repository);
                    if ($repository->has($guildPart->id)) {
                        $repository->pull($guildPart->id);
                    }
                }
            }
            $this->discord->guilds->pull($guildPart->id);
        }

        $deferred->resolve($guildPart);
    }
}
