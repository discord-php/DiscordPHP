<?php

namespace Discord\Parts\WebSockets;

use Carbon\Carbon;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\User\User;

class TypingStart extends Part
{
    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = ['user_id', 'timestamp', 'channel_id'];

    /**
     * Gets the user attribute.
     *
     * @return User 
     */
    public function getUserAttribute()
    {
    	return new User([
    		'id'	=> $this->user_id
    	], true);
    }

    /**
     * Gets the timestamp attribute.
     *
     * @return Carbon 
     */
    public function getTimestampAttribute()
    {
    	return new Carbon(gmdate('r', $this->attributes['timestamp']));
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel 
     */
    public function getChannelAttribute()
    {
    	return new Channel([
    		'id'	=> $this->channel_id
    	], true);
    }
}
