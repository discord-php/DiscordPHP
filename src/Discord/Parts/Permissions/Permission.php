<?php

namespace Discord\Parts\Permissions;

use Discord\Parts\Part;

abstract class Permission extends Part
{
    /**
     * Is the part editable?
     *
     * @var boolean 
     */
    public $editable = false;

    /**
     * Is the part creatable?
     *
     * @var boolean 
     */
    public $creatable = false;

    /**
     * Is the part deletable?
     *
     * @var boolean 
     */
    public $deletable = false;

    /**
     * Create a new part instance.
     * 
     * @param array $attributes
     * @param boolean $created 
     * @return void 
     */
    public function __construct(array $attributes = [], $created = false)
    {
        $this->attributes['perms'] = $this->default; // Default perms
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
        if ($key == 'perms') {
            $this->attributes['perms'] = $value;
            return;
        }

        if (!in_array($key, $this->bitoffset)) {
            return;
        }
        
        if (!is_bool($value)) {
            return;
        }

        $this->setBitwise($this->bitoffset[$key], $value);
    }

    /**
     * Gets an attribute on the part.
     *
     * @param string $key 
     * @return mixed 
     */
    public function getAttribute($key)
    {
        if ($key == 'perms') {
            return $this->attributes['perms'];
        }

        if (!in_array($key, $this->bitoffset)) {
            return;
        }

        if ((($this->perms >> $this->bitoffset[$key]) & 1) == 1) {
            return true;
        }

        return false;
    }

    /**
     * Sets a bitwise attribute.
     *
     * @param boolean $value 
     * @return boolean 
     */
    public function setBitwise($key, $value)
    {
        if ($value) {
            $this->attributes['perms'] |= (1 << $key);
        } else {
            $this->attributes['perms'] &= ~(1 << $key);
        }

        return $value;
    }

    /**
     * Returns an array of public attributes
     *
     * @return array 
     */
    public function getPublicAttributes()
    {
        $return = ['perms' => $this->attributes['perms']];

        foreach ($this->bitoffset as $key => $offset) {
            $return[$key] = $this->getAttribute($key);
        }

        return $return;
    }
}
