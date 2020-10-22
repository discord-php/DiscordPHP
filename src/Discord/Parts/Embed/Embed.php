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
     * Constructs a new instance.
     * @param array  $embed
     * @throws \Throwable
     */
    function __construct(array $embed = array()) {
        if (!empty($embed)) {
            $this->type = $embed['type'] ?? 'rich';
            
            if (!empty($embed['title'])) {
                $this->setTitle($embed['title']);
            }
            
            if (!empty($embed['author'])) {
                $this->setAuthor(
                    ((string) ($embed['author']['name'] ?? '')),
                    ((string) ($embed['author']['icon_url'] ?? '')),
                    ((string) ($embed['author']['url'] ?? ''))
                );
            }
            
            if (!empty($embed['description'])) {
                $this->setDescription($embed['description']);
            }
            
            $this->url = $embed['url'] ?? null;
            $this->timestamp = (!empty($embed['timestamp']) ? (new \DateTime($embed['timestamp']))->getTimestamp() : null);
            $this->color = $embed['color'] ?? null;
            
            if (!empty($embed['footer'])) {
                $this->setFooter(
                    ((string) ($embed['footer']['text'] ?? '')),
                    ((string) ($embed['footer']['icon_url'] ?? ''))
                );
            }
            
            if (!empty($embed['image'])) {
                $this->image = array(
                    'url' => ((string) $embed['image']['url']),
                    'height' => ((int) $embed['image']['height']),
                    'width' => ((int) $embed['image']['width'])
                );
            }
            
            if (!empty($embed['thumbnail'])) {
                $this->thumbnail = array(
                    'url' => ((string) $embed['thumbnail']['url']),
                    'height' => ((int) $embed['thumbnail']['height']),
                    'width' => ((int) $embed['thumbnail']['width'])
                );
            }
            
            if (!empty($embed['video'])) {
                $this->video = array(
                    'url' => ((string) $embed['video']['url']),
                    'height' => ((int) $embed['video']['height']),
                    'width' => ((int) $embed['video']['width'])
                );
            }
            
            if (!empty($embed['provider'])) {
                $this->provider = array(
                    'name' => ((string) $embed['provider']['name']),
                    'url' => ((string) $embed['provider']['url'])
                );
            }
            
            foreach (($embed['fields'] ?? array()) as $field) {
                $this->addFieldValues(
                    ((string) ($field['name'] ?? '')),
                    ((string) ($field['value'] ?? '')),
                    ((bool) ($field['inline'] ?? false))
                );
            }
        }
    }
    /**
     * {@inheritdoc}
     * @return mixed
     * @throws \Exception
     * @throws \RuntimeException
     * @internal
     */
    function __get($name) {
        if (\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch ($name) {
            case 'datetime':
                return (new \DateTime('@'.$this->timestamp));
            break;
        }
        
        return parent::__get($name);
    }
    
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
     * @return $this
     * @throws \RangeException
     * @param Field $field
     */
    public function addField(Field $field)
    {
        if (! isset($this->attributes['fields'])) {
            if (\count($this->fields) >= 25) {
                throw new \RangeException('Embeds can not have more than 25 fields');
            }
            $this->attributes['fields'] = [];
        }

        $this->attributes['fields'][] = $field->getRawAttributes();
        return $this;
    }
    
    
     /**
     * Adds a field to this embed.
     * @param string  $title    Maximum length is 256 characters.
     * @param string  $value    Maximum length is 1024 characters.
     * @param bool    $inline   Whether this field gets shown with other inline fields on one line.
     * @return $this
     * @throws \RangeException
     * @throws \InvalidArgumentException
     */
    public function addFieldValues($title, $value, bool $inline = false) {
    {
        if (! isset($this->attributes['fields'])) {
            if (\count($this->fields) >= 25) {
                throw new \RangeException('Embeds can not have more than 25 fields');
            }
            $this->attributes['fields'] = [];
        }

        $this->attributes['fields'][] = $field->getRawAttributes();
        return $this;
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
    
    /**
     * Set the author of this embed.
     * @param string  $name      Maximum length is 256 characters.
     * @param string  $iconurl   The URL to the icon.
     * @param string  $url       The URL to the author.
     * @return $this
     * @throws \InvalidArgumentException
     */
    function setAuthor($name, string $iconurl = '', string $url = '') {
        $name = (string) $name;
        
        if (\strlen($name) === 0) {
            $this->author = null;
            return $this;
        }
        
        if (\mb_strlen($name) > 256) {
            throw new \InvalidArgumentException('Author name can not be longer than 256 characters.');
        }
        
        if ($this->exceedsOverallLimit(\mb_strlen($name))) {
            throw new \InvalidArgumentException('Embed text values collectively can not exceed than 6000 characters');
        }
        
        $this->author = array(
            'name' => $name,
            'icon_url' => $iconurl,
            'url' => $url
        );
        
        return $this;
    }
    
    /**
     * Set the color of this embed.
     * @param mixed  $color
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setColor($color) {
        $this->color = $this->resolveColor($color);
        return $this;
    }
    
    /**
     * Resolves a color to an integer.
     * @param array|int|string  $color
     * @return int
     * @throws \InvalidArgumentException
     */
    static function resolveColor($color) {
        if (\is_int($color)) {
            return $color;
        }
        
        if (!\is_array($color)) {
            return \hexdec(((string) $color));
        }
        
        if (\count($color) < 1) {
            throw new \InvalidArgumentException('Color "'.\var_export($color, true).'" is not resolvable');
        }
        
        return (($color[0] << 16) + (($color[1] ?? 0) << 8) + ($color[2] ?? 0));
    }
    
    /**
     * Set the description of this embed.
     * @param string  $description  Maximum length is 2048 characters.
     * @return $this
     * @throws \InvalidArgumentException
     */
    function setDescription($description) {
        $description = (string) $description;
        
        if (\strlen($description) === 0) {
            $this->description = null;
            return $this;
        }
        
        if (\mb_strlen($description) > 2048) {
            throw new \InvalidArgumentException('Embed description can not be longer than 2048 characters');
        }
        
        if ($this->exceedsOverallLimit(\mb_strlen($description))) {
            throw new \InvalidArgumentException('Embed text values collectively can not exceed than 6000 characters');
        }
        
        $this->description = $description;
        return $this;
    }
    
    /**
     * Set the footer of this embed.
     * @param string  $text     Maximum length is 2048 characters.
     * @param string  $iconurl  The URL to the icon.
     * @return $this
     * @throws \InvalidArgumentException
     */
    function setFooter($text, string $iconurl = '') {
        $text = (string) $text;
        
        if (\strlen($text) === 0) {
            $this->footer = null;
            return $this;
        }
        
        if (\mb_strlen($text) > 2048) {
            throw new \InvalidArgumentException('Footer text can not be longer than 2048 characters.');
        }
        
        if ($this->exceedsOverallLimit(\mb_strlen($text))) {
            throw new \InvalidArgumentException('Embed text values collectively can not exceed than 6000 characters');
        }
        
        $this->footer = array(
            'text' => $text,
            'icon_url' => $iconurl
        );
        
        return $this;
    }
    
    /**
     * Set the image of this embed.
     * @param string  $url
     * @return $this
     */
    function setImage($url) {
        $this->image = array('url' => (string) $url);
        return $this;
    }
    
    /**
     * Set the thumbnail of this embed.
     * @param string  $url
     * @return $this
     */
    function setThumbnail($url) {
        $this->thumbnail = array('url' => (string) $url);
        return $this;
    }
    
    /**
     * Set the timestamp of this embed.
     * @param int|null  $timestamp
     * @return $this
     * @throws \Exception
     */
    function setTimestamp(?int $timestamp = null) {
        $this->timestamp = (new \DateTime(($timestamp !== null ? '@'.$timestamp : 'now')))->format('c');
        return $this;
    }
    
    /**
     * Set the title of this embed.
     * @param string  $title    Maximum length is 256 characters.
     * @return $this
     * @throws \InvalidArgumentException
     */
    function setTitle(string $title) {
        if (\strlen($title) == 0) {
            $this->title = null;
            return $this;
        }
        
        if (\mb_strlen($title) > 256) {
            throw new \InvalidArgumentException('Embed title can not be longer than 256 characters');
        }
        
        if ($this->exceedsOverallLimit(\mb_strlen($title))) {
            throw new \InvalidArgumentException('Embed text values collectively can not exceed than 6000 characters');
        }
        
        $this->title = $title;
        return $this;
    }
    
    /**
     * Set the URL of this embed.
     * @param string  $url
     * @return $this
     */
    function setURL(string $url) {
        $this->url = $url;
        return $this;
    }
    
    /**
     * Checks to see if adding a property has put us over Discord's 6000-char overall limit.
     * @param int  $addition
     * @return bool
     */
    protected function exceedsOverallLimit(int $addition): bool {
        $total = (
            \mb_strlen(($this->title ?? "")) +
            \mb_strlen(($this->description ?? "")) +
            \mb_strlen(($this->footer['text'] ?? "")) +
            \mb_strlen(($this->author['name'] ?? "")) +
            $addition
        );
        
        foreach ($this->fields as $field) {
            $total += \mb_strlen($field['name']);
            $total += \mb_strlen($field['value']);
        }
        
        return ($total > 6000);
    }
}
