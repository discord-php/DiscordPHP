<?php

namespace Discord\Parts;

class Role
{
	public $id;
	public $name;
	public $color;
	public $managed;
	public $permissions;
	public $position;
	public $hoist;
	protected $guzzle;

	public function __construct($id, $name, $color, $managed, $permissions, $position, $hoist, $guzzle)
	{
		$this->id = $id;
		$this->name = $name;
		$this->color = $color;
		$this->managed = $managed;
		$this->permissions = $permissions;
		$this->position = $position;
		$this->hoist = $hoist;

		$this->guzzle = $guzzle;
	}
}