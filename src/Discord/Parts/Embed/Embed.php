<?php

namespace Discord\Parts\Embed;

use Carbon\Carbon;
use Discord\Helpers\Collection;
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
    protected $fillable = ['title', 'type', 'description', 'url', 'timestamp', 'color', 'footer', 'image', 'thumbnail', 'video', 'provider', 'author', 'fields'];

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

    /**
     * Gets the footer attribute.
     *
     * @return Footer The footer attribute.
     */
    public function getFooterAttribute()
    {
        return $this->attributeHelper('footer', Footer::class);
    }

    /**
     * Gets the image attribute.
     *
     * @return Image The image attribute.
     */
    public function getImageAttribute()
    {
        return $this->attributeHelper('image', Image::class);
    }

    /**
     * Gets the thumbnail attribute.
     *
     * @return Thumbnail The thumbnail attribute.
     */
    public function getThumbnailAttribute()
    {
        return $this->attributeHelper('thumbnail', Image::class);
    }

    /**
     * Gets the video attribute.
     *
     * @return Video The video attribute.
     */
    public function getVideoAttribute()
    {
        return $this->attributeHelepr('video', Video::class);
    }

    /**
     * Gets the author attribute.
     *
     * @return Author The author attribute.
     */
    public function getAuthorAttribute()
    {
        return $this->attributeHelper('author', Author::class);
    }

    /**
     * Gets the fields attribute.
     *
     * @return Collection[Field] The fields attribute.
     */
    public function getFieldsAttribute()
    {
        $fields = new Collection();

        foreach ($this->attributes['fields'] as $field) {
            if (! ($field instanceof Field)) {
                $field = $this->discord->factory(Field::class, $field, true);
            }

            $fields->push($field);
        }

        return $fields;
    }

    /**
     * Helps with getting embed attributes.
     *
     * @param string $key   The attribute key.
     * @param string $class The attribute class.
     *
     * @return mixed
     */
    protected function attributeHelper($key, $class)
    {
        if ($this->attributes[$key] instanceof $class) {
            return $this->attributes[$key];
        }

        return $this->discord->factory($class, $this->attributes[$key], true);
    }
}
