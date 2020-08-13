<?php

namespace Discord\Parts\Channel;

use Discord\Parts\Part;

use function PHPSTORM_META\map;

class Webhook extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'type',
        'guild_id',
        'channel_id',
        'user',
        'name',
        'avatar',
        'token'
    ];

    /**
     * Gets the guild the webhook belongs to.
     * 
     * @return \Discord\Parts\Guild\Guild 
     */
    public function getGuildAttribute()
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the channel the webhook belongs to.
     * 
     * @return  \Discord\Parts\Channel\Channel
     */
    public function getChannelAttribute()
    {
        if ($guild = $this->getGuildAttribute()) {
            return $guild->channels->get('id', $this->channel_id);
        }
    }

    /**
     * Returns the attributes needed to edit.
     * 
     * @return  array
     */
    public function getUpdatableAttributes()
    {
        return [
            'name' => $this->name,
            'avatar' => $this->avatar,
            'channel_id' => $this->channel_id,
        ];
    }

    /**
     * Returns the attributes needed to create.
     * 
     * @return  array
     */
    public function getCreatableAttributes()
    {
        return [
            'name' => $this->name,
            'avatar' => $this->avatar
        ];
    }
}