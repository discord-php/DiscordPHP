<?php

namespace Discord\Parts;

use ArrayAccess;
use Serializable;
use JsonSerializable;
use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Exceptions\PartRequestFailedException;
use Discord\Helpers\Guzzle;
use Illuminate\Support\Str;

abstract class Part implements ArrayAccess, Serializable, JsonSerializable
{
    /**
     * The parts fillable attributes.
     *
     * @var array 
     */
    protected $fillable = [];

    /**
     * Extra fillable defined by the base part.
     *
     * @var array 
     */
    protected $extra_fillable = [];

    /**
     * The parts attributes.
     * 
     * @var array
     */
    protected $attributes = [];

    /**
     * The parts attributes cache.
     *
     * @var array 
     */
    protected $attributes_cache = [];

    /**
     * Attributes that are hidden from debug info.
     *
     * @var array 
     */
    protected $hidden = [];

    /**
     * Is the part already created in the Discord servers?
     *
     * @var boolean 
     */
    protected $created = false;

    /**
     * Is the part deleted in the Discord servers?
     *
     * @var boolean 
     */
    protected $deleted = false;

    /**
     * The regex pattern to replace variables with.
     *
     * @var string 
     */
    protected $regex = '/:([a-z_]+)/';

    /**
     * Is the part findable?
     *
     * @var boolean 
     */
    public $findable = true;

    /**
     * Is the part creatable?
     *
     * @var boolean 
     */
    public $creatable = true;

    /**
     * Is the part deletable?
     *
     * @var boolean 
     */
    public $deletable = true;

    /**
     * Is the part editable?
     *
     * @var boolean 
     */
    public $editable = true;

    /**
     * Should we fill the part after saving?
     *
     * @var boolean 
     */
    protected $fillAfterSave = true;

    /**
     * Create a new part instance.
     * 
     * @param array $attributes
     * @param boolean $created 
     * @return void 
     */
    public function __construct(array $attributes = [], $created = false)
    {
        $this->created = $created;
        $this->fill($attributes);

        if (is_callable([$this, 'afterConstruct'])) {
            $this->afterConstruct();
        }
    }

    /**
     * Attempts to get the part from the servers and
     * return it.
     *
     * @param string $id 
     * @return Part|null 
     */
    public static function find($id)
    {
        $part = new static([], true);

        if (!$part->findable) {
            return null;
        }

        try {
            $request = Guzzle::get($part->uriReplace('get', ['id' => $id]));
        } catch (DiscordRequestFailedException $e) {
            return null;
        }

        $part->fill($request);

        return $part;
    }

    /**
     * Fills the parts attributes from an array.
     *
     * @param array $attributes
     * @return void 
     */
    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable + $this->extra_fillable)) {
                $this->setAttribute($key, $value);
            }
        }
    }

    /**
     * Gets a fresh copy of the part.
     *
     * @return boolean 
     */
    public function fresh()
    {
        if ($this->deleted || !$this->created) {
            return false;
        }

        $request = Guzzle::get($this->get);

        $this->fill($request);

        return true;
    }

    /**
     * Saves the part to the Discord servers.
     *
     * @return boolean 
     */
    public function save()
    {
        $attributes = $this->created ? $this->getUpdatableAttributes() : $this->getCreatableAttributes();

        try {
            if ($this->created) {
                if (!$this->editable) {
                    return false;
                }

                $request = Guzzle::patch($this->replaceWithVariables($this->uris['update']), $attributes);
            } else {
                if (!$this->creatable) {
                    return false;
                }
                
                $request = Guzzle::post($this->replaceWithVariables($this->uris['create']), $attributes);
                $this->created = true;
                $this->deleted = false;
            }
        } catch (\Exception $e) {
            throw new PartRequestFailedException($e->getMessage());
        }

        if ($this->fillAfterSave) {
            $this->fill($request);
        }

        return true;
    }

    /**
     * Deletes the part on the Discord servers.
     *
     * @return boolean 
     */
    public function delete()
    {
        if (!$this->deletable) {
            return false;
        }

        try {
            $request = Guzzle::delete($this->replaceWithVariables($this->uris['delete']));
            $this->created = false;
            $this->deleted = true;
        } catch (\Exception $e) {
            throw new PartRequestFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * Clears the attribute cache.
     *
     * @return boolean 
     */
    public function clearCache()
    {
        $this->attributes_cache = [];

        return true;
    }

    /**
     * Checks if there is a mutator present.
     *
     * @param string $key 
     * @param string $type
     * @return mixed 
     */
    public function checkForMutator($key, $type)
    {
        $str = $type.Str::studly($key).'Attribute';

        if (is_callable([$this, $str])) {
            return $str;
        }

        return false;
    }

    /**
     * Replaces variables in string with syntax :{varname}
     *
     * @param string $string 
     * @return string 
     */
    public function replaceWithVariables($string)
    {
        $matches = null;
        $matcher = preg_match_all($this->regex, $string, $matches);

        $original = $matches[0];
        $vars = $matches[1];

        foreach ($vars as $key => $variable) {
            if ($attribute = $this->getAttribute($variable)) {
                $string = str_replace($original[$key], $attribute, $string);
            }
        }

        return $string;
    }

    /**
     * Replaces variables in one of the URIs.
     *
     * @param string $key 
     * @param array $params 
     * @return string 
     */
    public function uriReplace($key, $params)
    {
        $string = $this->uris[$key];

        $matches = null;
        $matcher = preg_match_all($this->regex, $string, $matches);

        $original = $matches[0];
        $vars = $matches[1];

        foreach ($vars as $key => $variable) {
            if ($attribute = $params[$variable]) {
                $string = str_replace($original[$key], $attribute, $string);
            }
        }

        return $string;
    }

    /**
     * Gets an attribute on the part.
     *
     * @param string $key 
     * @return mixed 
     */
    public function getAttribute($key)
    {
        if ($str = $this->checkForMutator($key, 'get')) {
            return $this->{$str}();
        }

        if (!isset($this->attributes[$key])) {
            return null;
        }

        return $this->attributes[$key];
    }

    /**
     * Sets an attribute on the part.
     *
     * @param string $key 
     * @param mixed $value 
     * @return void 
     */
    public function setAttribute($key, $value)
    {
        if ($str = $this->checkForMutator($key, 'set')) {
            $this->{$str}($value);
            return;
        }

        $this->attributes[$key] = $value;
    }

    /**
     * Sets a cache attribute on the part.
     *
     * @param string $key 
     * @param mixed $value 
     * @return void 
     */
    public function setCache($key, $value)
    {
        $this->attributes_cache[$key] = $value;
    }

    /**
     * Gets an attribute via key. Used for ArrayAccess.
     *
     * @param string $key
     * @return mixed 
     */
    public function offsetGet($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Checks if an attribute exists via key. Used for ArrayAccess.
     *
     * @param string $key 
     * @return mixed 
     */
    public function offsetExists($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Sets an attribute via key. Used for ArrayAccess.
     *
     * @param string $key 
     * @param mixed $value 
     * @return void 
     */
    public function offsetSet($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Unsets an attribute via key. Used for ArrayAccess.
     *
     * @param string $key 
     * @return void 
     */
    public function offsetUnset($key)
    {
        if (isset($this->attributes[$key])) {
            unset($this->attributes[$key]);
        }
    }

    /**
     * Serializes the data. Used for Serializable.
     *
     * @return mixed 
     */
    public function serialize()
    {
        return serialize($this->attributes);
    }

    /**
     * Unserializes some data. Used for Serializable.
     *
     * @param mixed $data 
     * @return mixed 
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        foreach ($data as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * Provides data when the part is encoded into
     * JSON. Used for JsonSerializable.
     *
     * @return array 
     */
    public function jsonSerialize()
    {
        return $this->getPublicAttributes();
    }

    /**
     * Returns an array of public attributes
     *
     * @return array 
     */
    public function getPublicAttributes()
    {
        $data = [];

        foreach ($this->attributes as $key => $value) {
            if (in_array($key, $this->hidden)) {
                continue;
            }

            $data[$key] = $this->getAttribute($key);
        }

        return $data;
    }
    
    /**
     * Converts the part to a string.
     *
     * @return string 
     */
    public function __toString()
    {
        return json_encode($this->getPublicAttributes());
    }

    /**
     * Handles debug calls from var_dump and similar functions.
     *
     * @return array 
     */
    public function __debugInfo()
    {
        return $this->getPublicAttributes();
    }

    /**
     * Handles dynamic get calls onto the part.
     *
     * @param string $key 
     * @return mixed 
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Handles dynamic set calls onto the part.
     *
     * @param string $key 
     * @param mixed $value 
     * @return void 
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }
}
