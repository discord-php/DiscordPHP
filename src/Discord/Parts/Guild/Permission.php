<?php

namespace Discord\Parts\Guild;

use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

class Permission extends Part
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
        $this->attributes['perms'] = 36953089; // Default perms
    }
    
    /**
     * The Bit Offset map.
     *
     * @var array 
     */
    protected $bitoffset = [
        'create_instant_invite' => 0,
        'kick_members'          => 1,
        'ban_members'           => 2,
        'manage_roles'          => 3,
        'manage_permissions'    => 3,
        'manage_channels'       => 4,
        'manage_channel'        => 4,
        'manage_server'         => 5,

        'read_messages'         => 10,
        'send_messages'         => 11,
        'send_tts_messages'     => 12,
        'manage_messages'       => 13,
        'embed_links'           => 14,
        'attach_files'          => 15,
        'read_message_history'  => 16,
        'mention_everyone'      => 17,

        'voice_connect'         => 20,
        'voice_speak'           => 21,
        'voice_mute_members'    => 22,
        'voice_deafen_members'  => 23,
        'voice_move_members'    => 24,
        'voice_use_vad'         => 25
    ];

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
