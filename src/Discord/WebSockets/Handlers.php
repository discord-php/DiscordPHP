<?php

namespace Discord\WebSockets;

use Discord\WebSockets\Event;

class Handlers
{
	/**
	 * An array of handlers.
	 *
	 * @var array 
	 */
	protected $handlers = [];

	/**
	 * Constructs the list of handlers.
	 *
	 * @return void 
	 */
	public function __construct()
	{
		$this->addHandler(Event::MESSAGE_CREATE, \Discord\WebSockets\Events\MessageCreate::class);	
	}

	/**
	 * Adds a handler to the list.
	 *
	 * @param string $event 
	 * @param string $classname
	 * @return void 
	 */
	public function addHandler($event, $classname)
	{
		$this->handlers[$event] = $classname;
	}

	/**
	 * Returns a handler.
	 *
	 * @param string $event 
	 * @return string|null 
	 */
	public function getHandler($event)
	{
		if (isset($this->handlers[$event])) {
			return $this->handlers[$event];
		}

		return null;
	}
}