<?php

namespace Discord\Parts\Guild;

class Channel
{
	public $id;
	public $name;
	public $type;
	public $guild_id;
	public $guild;
	public $is_private;
	public $position;
	public $last_message_id;
	protected $guzzle;

	public function __construct($id, $name, $type, $guild, $is_private, $position, $last_message_id, $guzzle)
	{
		$this->id = $id;
		$this->name = $name;
		$this->type = $type;
		$this->guild_id = $guild->id;
		$this->guild = $guild;
		$this->is_private = $is_private;
		$this->position = $position;
		$this->last_message_id = $last_message_id;
		$this->guzzle = $guzzle;
	}
}