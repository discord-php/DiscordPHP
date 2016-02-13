<?php

namespace Discord\Parts\Channel;

use Discord\Parts\Part;
use Discord\Parts\Permissions\ChannelPermission;

class Overwrite extends Part
{
    /**
     * {@inheritdoc}
     */
    public $findable = false;

    /**
     * {@inheritdoc}
     */
    public $creatable = false;

    /**
     * {@inheritdoc}
     */
    public $deletable = false;

    /**
     * {@inheritdoc}
     */
    public $editable = false;

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'type', 'allow', 'deny'];

    /**
     * {@inheritdoc}
     */
    protected $uris = [];

    /**
     * Returns the allow attribute.
     *
     * @return ChannelPermission The allow attribute.
     */
    public function getAllowAttribute()
    {
    	$perm = new ChannelPermission();
    	$perm->perms = $this->attributes['allow'];

    	return $perm;
    }

    /**
     * Returns the deny attribute.
     *
     * @return ChannelPermission The deny attribute.
     */
    public function getDenyAttribute()
    {
    	$perm = new ChannelPermission();
    	$perm->perms = $this->attributes['deny'];

    	return $perm;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        return [];
    }
}