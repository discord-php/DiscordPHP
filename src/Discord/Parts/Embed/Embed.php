<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Embed;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Parts\Part;

/**
 * An embed object to be sent with a message.
 *
 * @property string            $title       The title of the embed.
 * @property string            $type        The type of the embed.
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
 * @property Field[]           $fields      A collection of embed fields.
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
    protected function getTimestampAttribute(): Carbon
    {
        if (! array_key_exists('timestamp', $this->attributes)) {
            return Carbon::now();
        }

        if (! empty($this->attributes['timestamp'])) {
            return Carbon::parse($this->attributes['timestamp']);
        }
    }

    /**
     * Gets the footer attribute.
     *
     * @return Footer The footer attribute.
     */
    protected function getFooterAttribute(): Footer
    {
        return $this->attributeHelper('footer', Footer::class);
    }

    /**
     * Gets the image attribute.
     *
     * @return Image The image attribute.
     */
    protected function getImageAttribute(): Image
    {
        return $this->attributeHelper('image', Image::class);
    }

    /**
     * Gets the thumbnail attribute.
     *
     * @return Image The thumbnail attribute.
     */
    protected function getThumbnailAttribute(): Image
    {
        return $this->attributeHelper('thumbnail', Image::class);
    }

    /**
     * Gets the video attribute.
     *
     * @return Video The video attribute.
     */
    protected function getVideoAttribute(): Video
    {
        return $this->attributeHelper('video', Video::class);
    }

    /**
     * Gets the author attribute.
     *
     * @return Author The author attribute.
     */
    protected function getAuthorAttribute(): Author
    {
        return $this->attributeHelper('author', Author::class);
    }

    /**
     * Gets the fields attribute.
     *
     * @return Collection|Field[]
     */
    protected function getFieldsAttribute(): Collection
    {
        $fields = new Collection([], 'name', Field::class);

        if (! array_key_exists('fields', $this->attributes)) {
            return $fields;
        }

        foreach ($this->attributes['fields'] as $field) {
            if (! ($field instanceof Field)) {
                $field = $this->factory->create(Field::class, (array) $field, true);
            }

            $fields->push($field);
        }

        return $fields;
    }

    /**
     * Sets the fields attribute.
     *
     * @param array[Field] $fields
     */
    protected function setFieldsAttribute($fields)
    {
        $this->attributes['fields'] = [];

        foreach ($fields as $field) {
            if ($field instanceof Field) {
                $field = $field->getRawAttributes();
            }

            $this->attributes['fields'][] = $field;
        }
    }

    /**
     * Adds a field to the embed.
     *
     * @param Field $field
     */
    public function addField(Field $field)
    {
        if (! isset($this->attributes['fields'])) {
            $this->attributes['fields'] = [];
        }

        $this->attributes['fields'][] = $field->getRawAttributes();
    }

    /**
     * Helps with getting embed attributes.
     *
     * @param string $key   The attribute key.
     * @param string $class The attribute class.
     *
     * @return mixed
     * @throws \Exception
     */
    private function attributeHelper($key, $class)
    {
        if (! array_key_exists($key, $this->attributes)) {
            return $this->factory->create($class, []);
        }

        if ($this->attributes[$key] instanceof $class) {
            return $this->attributes[$key];
        }

        return $this->factory->create($class, $this->attributes[$key], true);
    }
}
