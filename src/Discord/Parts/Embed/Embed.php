<?php 

namespace Discord\Parts\Embed;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Parts\Embed\Footer;
use Discord\Parts\Embed\Image;
use Discord\Parts\Embed\Video;
use Discord\Parts\Part;

/**
 * An embed object to be sent with a message.
 *
 * @property string            $title       The title of the embed.
 * @property string            $description A description of the embed.
 * @property string            $url         The URL of the embed.
 * @property Carbon|string     $timestamp   A timestamp of the embed.
 * @property int               $color       The color of the embed.
 * @property Footer            $footer      The footer of the embed.
 * @property Image             $image       The image of the embed.
 * @property Image             $thumbnail   The thumbnail of the embed.
 * @property Video             $video       The video of the embed.
 * @property array             $provider    The provider of the embed.
 * @property Author            $author      The author of the embed.
 * @property Collection[Field] $fields      A collection of embed fields.
 */
class Embed extends Part
{
	/**
	 * {@inheritdoc}
	 */
	protected $fillable = ['title', 'type', 'description', 'url', 'timestamp', 'color',
						   'footer', 'image', 'thumbnail', 'video', 'provider', 'author', 'fields'];

    /**
     * Gets the timestamp attribute.
     *
     * @return Carbon The timestamp attribute.
     */
    public function getTimestampAttribute()
    {
    	if (! array_key_exists('timestamp', $this->attributes)) {
    		return Carbon::now();
    	}

    	return Carbon::parse($this->attributes['timestamp']);
    }
}