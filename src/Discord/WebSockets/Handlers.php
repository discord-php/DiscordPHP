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

		// Guild Event handlers
		$this->addHandler(Event::GUILD_CREATE, \Discord\WebSockets\Events\GuildCreate::class);
		$this->addHandler(Event::GUILD_DELETE, \Discord\WebSockets\Events\GuildDelete::class);
		$this->addHandler(Event::GUILD_UPDATE, \Discord\WebSockets\Events\GuildUpdate::class);

		// Channel Event handlers
		$this->addHandler(Event::CHANNEL_CREATE, \Discord\WebSockets\Events\ChannelCreate::class);
		$this->addHandler(Event::CHANNEL_UPDATE, \Discord\WebSockets\Events\ChannelUpdate::class);
		$this->addHandler(Event::CHANNEL_DELETE, \Discord\WebSockets\Events\ChannelDelete::class);

		// Ban Event handlers
		$this->addHandler(Event::GUILD_BAN_ADD, \Discord\WebSockets\Events\GuildBanAdd::class);
		$this->addHandler(Event::GUILD_BAN_REMOVE, \Discord\WebSockets\Events\GuildBanRemove::class);

		// New Member Event handlers
		$this->addHandler(Event::GUILD_MEMBER_ADD, \Discord\WebSockets\Events\GuildMemberAdd::class);
		$this->addHandler(Event::GUILD_MEMBER_REMOVE, \Discord\WebSockets\Events\GuildMemberRemove::class);
		$this->addHandler(Event::GUILD_MEMBER_UPDATE, \Discord\WebSockets\Events\GuildMemberUpdate::class);

		// New Role Event handlers
		$this->addHandler(Event::GUILD_ROLE_CREATE, \Discord\WebSockets\Events\GuildRoleCreate::class);
		$this->addHandler(Event::GUILD_ROLE_DELETE, \Discord\WebSockets\Events\GuildRoleDelete::class);
		$this->addHandler(Event::GUILD_ROLE_UPDATE, \Discord\WebSockets\Events\GuildRoleUpdate::class);
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