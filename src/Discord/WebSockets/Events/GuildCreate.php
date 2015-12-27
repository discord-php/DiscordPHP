<?php

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;

class GuildCreate extends Event
{
    /**
     * Returns the formatted data.
     *
     * @param array $data 
     * @param Discord $discord 
     * @return Message 
     */
    public function getData($data, $discord)
    {
        $guildPart = new Guild([
            'id'                => $data->id,
            'name'              => $data->name,
            'icon'              => $data->icon,
            'region'            => $data->region,
            'owner_id'          => $data->owner_id,
            'roles'             => $data->roles,
            'joined_at'         => $data->joined_at,
            'afk_channel_id'    => $data->afk_channel_id,
            'afk_timeout'       => $data->afk_timeout,
            'large'             => $data->large,
            'features'          => $data->features,
            'splash'            => $data->splash,
            'emojis'            => $data->emojis
        ], true);

        $channels = new Collection();

        foreach ($data->channels as $channel) {
            $channelPart = new Channel([
                'id'                    => $channel->id,
                'name'                  => $channel->name,
                'type'                  => $channel->type,
                'topic'                 => $channel->topic,
                'guild_id'              => $data->id,
                'position'              => $channel->position,
                'last_message_id'       => $channel->last_message_id,
                'permission_overwrites' => $channel->permission_overwrites
            ], true);

            $channels->push($channelPart);
        }

        $guildPart->setCache('channels', $channels);

        // guild members
        $members = new Collection();

        foreach ($data->members as $member) {
            $memberPart = new Member([
                'user'      => $member->user,
                'roles'     => $member->roles,
                'mute'      => $member->mute,
                'deaf'      => $member->deaf,
                'joined_at' => $member->joined_at,
                'guild_id'  => $data->id,
                'status'    => 'offline',
                'game'      => null
            ], true);

            // check for presences
            
            foreach ($data->presences as $presence) {
                if ($presence->user->id == $member->user->id) {
                    $memberPart->status = $presence->status;
                    $memberPart->game = $presence->game;
                }
            }

            $members->push($memberPart);
        }

        $guildPart->setCache('members', $members);

        return $guildPart;
    }

    /**
     * Updates the Discord instance with the new data.
     *
     * @param mixed $data 
     * @param Discord $discord 
     * @return Discord 
     */
    public function updateDiscordInstance($data, $discord)
    {
        $discord->guilds->push($data);

        return $discord;
    }
}