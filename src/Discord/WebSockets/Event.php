<?php

namespace Discord\WebSockets;

class Event
{
	// Constants

	const MESSAGE_CREATE 	= 'MESSAGE_CREATE';
	const READY 			= 'READY';

	/**
	 * The data in array format.
	 *
	 * @var array 
	 */
	protected $data;

	/**
	 * Handles construction of the event.
	 *
	 * @param mixed $data 
	 * @return void 
	 */
	public function __construct($data)
	{
		$this->data = $data;
	}
}