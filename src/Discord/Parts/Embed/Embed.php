<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Embed;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Attachment;
use Discord\Parts\Part;
use function Discord\poly_strlen;

/**
 * An embed object to be sent with a message.
 *
 * @property string|null        $title       The title of the embed.
 * @property string|null        $type        The type of the embed.
 * @property string|null        $description A description of the embed.
 * @property string|null        $url         The URL of the embed.
 * @property Carbon|string|null $timestamp   A timestamp of the embed.
 * @property int|null           $color       The color of the embed.
 * @property Footer|null        $footer      The footer of the embed.
 * @property Image|null         $image       The image of the embed.
 * @property Image|null         $thumbnail   The thumbnail of the embed.
 * @property Video|null         $video       The video of the embed.
 * @property object|null        $provider    The provider of the embed.
 * @property Author|null        $author      The author of the embed.
 * @property Collection|Field[] $fields      A collection of embed fields.
 */
class Embed extends Part
{
    public const TYPE_RICH = 'rich';
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_GIFV = 'gifv';
    public const TYPE_ARTICLE = 'article';
    public const TYPE_LINK = 'link';

    /**
     * @inheritdoc
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
                $field = $this->factory->create(Field::class, $field, true);
            }

            $fields->push($field);
        }

        return $fields;
    }

    /**
     * Sets the fields attribute.
     *
     * @param Field[] $fields
     */
    protected function setFieldsAttribute($fields)
    {
        $this->attributes['fields'] = [];
        $this->addField(...$fields);
    }

    /**
     * Sest the color of this embed.
     *
     * @param mixed $color
     *
     * @throws \InvalidArgumentException
     */
    protected function setColorAttribute($color)
    {
        $this->attributes['color'] = $this->resolveColor($color);
    }

    /**
     * Sets the description of this embed.
     *
     * @param string $description Maximum length is 4096 characters.
     *
     * @throws \LengthException
     */
    protected function setDescriptionAttribute($description)
    {
        if (poly_strlen($description) === 0) {
            $this->attributes['description'] = null;
        } elseif (poly_strlen($description) > 4096) {
            throw new \LengthException('Embed description can not be longer than 4096 characters');
        } else {
            if ($this->exceedsOverallLimit(poly_strlen($description))) {
                throw new \LengthException('Embed text values collectively can not exceed than 6000 characters');
            }

            $this->attributes['description'] = $description;
        }
    }

    /**
     * Sets the type of the embed.
     *
     * @param string $type
     *
     * @throws \InvalidArgumentException
     */
    protected function setTypeAttribute($type)
    {
        if (! in_array($type, $this->getEmbedTypes())) {
            throw new \InvalidArgumentException('Given type "'.$type.'" is not a valid embed type.');
        }

        $this->attributes['type'] = $type;
    }

    /**
     * Set the title of this embed.
     *
     * @param string $title Maximum length is 256 characters.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    protected function setTitleAttribute(string $title): self
    {
        if (poly_strlen($title) == 0) {
            $this->attributes['title'] = null;
        } elseif (poly_strlen($title) > 256) {
            throw new \LengthException('Embed title can not be longer than 256 characters');
        } elseif ($this->exceedsOverallLimit(poly_strlen($title))) {
            throw new \LengthException('Embed text values collectively can not exceed than 6000 characters');
        } else {
            $this->attributes['title'] = $title;
        }

        return $this;
    }

    /**
     * Sets the title of the embed.
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Sets the type of the embed.
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Sets the description of the embed.
     *
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Sets the color of the embed.
     *
     * @param mixed $color
     *
     * @return $this
     */
    public function setColor($color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Adds a field to the embed.
     *
     * @param Field|array $field
     *
     * @throws \OverflowException
     *
     * @return $this
     */
    public function addField(...$fields): self
    {
        foreach ($fields as $field) {
            if (count($this->fields) > 25) {
                throw new \OverflowException('Embeds can not have more than 25 fields.');
            }

            if ($field instanceof Field) {
                $field = $field->getRawAttributes();
            }

            $this->attributes['fields'][] = $field;
        }

        return $this;
    }

    /**
     * Adds a field to the embed with values.
     *
     * @param string $name   Maximum length is 256 characters.
     * @param string $value  Maximum length is 1024 characters.
     * @param bool   $inline Whether this field gets shown with other inline fields on one line.
     *
     * @throws \OverflowException
     *
     * @return $this
     */
    public function addFieldValues(string $name, string $value, bool $inline = false)
    {
        return $this->addField([
            'name' => $name,
            'value' => $value,
            'inline' => $inline,
        ]);
    }

    /**
     * Set the author of this embed.
     *
     * @param string $name    Maximum length is 256 characters.
     * @param string $iconurl The URL to the icon.
     * @param string $url     The URL to the author.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setAuthor(string $name, string $iconurl = '', string $url = ''): self
    {
        if (poly_strlen($name) === 0) {
            $this->author = null;
        } elseif (poly_strlen($name) > 256) {
            throw new \LengthException('Author name can not be longer than 256 characters.');
        } elseif ($this->exceedsOverallLimit(poly_strlen($name))) {
            throw new \LengthException('Embed text values collectively can not exceed than 6000 characters');
        } else {
            $this->author = [
                'name' => $name,
                'icon_url' => $iconurl,
                'url' => $url,
            ];
        }

        return $this;
    }

    /**
     * Set the footer of this embed.
     *
     * @param string $text    Maximum length is 2048 characters.
     * @param string $iconurl The URL to the icon.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setFooter(string $text, string $iconurl = ''): self
    {
        if (poly_strlen($text) === 0) {
            $this->footer = null;
        } elseif (poly_strlen($text) > 2048) {
            throw new \LengthException('Footer text can not be longer than 2048 characters.');
        } elseif ($this->exceedsOverallLimit(poly_strlen($text))) {
            throw new \LengthException('Embed text values collectively can not exceed than 6000 characters');
        } else {
            $this->footer = [
                'text' => $text,
                'icon_url' => $iconurl,
            ];
        }

        return $this;
    }

    /**
     * Set the image of this embed.
     *
     * @param string|Attachment $url
     *
     * @return $this
     */
    public function setImage($url): self
    {
        if ($url instanceof Attachment) {
            $this->image = ['url' => 'attachment://'.$url->filename];
        } else {
            $this->image = ['url' => (string) $url];
        }

        return $this;
    }

    /**
     * Set the thumbnail of this embed.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setThumbnail($url): self
    {
        $this->thumbnail = ['url' => (string) $url];

        return $this;
    }

    /**
     * Set the timestamp of this embed.
     *
     * @param int|null $timestamp
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setTimestamp(?int $timestamp = null): self
    {
        $this->timestamp = (new Carbon(($timestamp !== null ? '@'.$timestamp : 'now')))->format('c');

        return $this;
    }

    /**
     * Set the URL of this embed.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setURL(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Checks to see if adding a property has put us over Discord's 6000-char overall limit.
     *
     * @param int $addition
     *
     * @return bool
     */
    protected function exceedsOverallLimit(int $addition): bool
    {
        $total = (
            poly_strlen(($this->title ?? '')) +
            poly_strlen(($this->description ?? '')) +
            poly_strlen(($this->footer['text'] ?? '')) +
            poly_strlen(($this->author['name'] ?? '')) +
            $addition
        );

        foreach ($this->fields as $field) {
            $total += poly_strlen($field['name']);
            $total += poly_strlen($field['value']);
        }

        return ($total > 6000);
    }

    /**
     * Resolves a color to an integer.
     *
     * @param array|int|string $color
     *
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    protected static function resolveColor($color)
    {
        if (is_int($color)) {
            return $color;
        }

        if (! is_array($color)) {
            return hexdec((str_replace('#', '', (string) $color)));
        }

        if (count($color) < 1) {
            throw new \InvalidArgumentException('Color "'.var_export($color, true).'" is not resolvable');
        }

        return (($color[0] << 16) + (($color[1] ?? 0) << 8) + ($color[2] ?? 0));
    }

    /**
     * Helps with getting embed attributes.
     *
     * @param string $key   The attribute key.
     * @param string $class The attribute class.
     *
     * @throws \Exception
     *
     * @return mixed
     */
    private function attributeHelper($key, $class)
    {
        if (! array_key_exists($key, $this->attributes)) {
            return $this->factory->create($class);
        }

        if ($this->attributes[$key] instanceof $class) {
            return $this->attributes[$key];
        }

        return $this->factory->create($class, $this->attributes[$key], true);
    }

    /**
     * Returns all possible embed types.
     *
     * @return array
     */
    private static function getEmbedTypes()
    {
        return [
            self::TYPE_RICH,
            self::TYPE_IMAGE,
            self::TYPE_VIDEO,
            self::TYPE_GIFV,
            self::TYPE_ARTICLE,
            self::TYPE_LINK,
        ];
    }
}
