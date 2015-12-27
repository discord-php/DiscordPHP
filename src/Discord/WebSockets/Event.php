<?php

namespace Discord\WebSockets;

class Event
{
	const READY 			= 'READY';
	const MESSAGE_CREATE 	= 'MESSAGE_CREATE';

	const CHANNEL_CREATE	= 'CHANNEL_CREATE';
	const CHANNEL_DELETE	= 'CHANNEL_DELETE';
	const CHANNEL_UPDATE	= 'CHANNEL_UPDATE';
}