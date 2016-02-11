<?php

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\WebSocket;

// Includes the Composer autoload file
include '../vendor/autoload.php';

if ($argc < 3) {
	echo "You must pass your Email and Password into the cmdline. Example: php basic.php <email> <password>";
	die(1);
}

// Init the Discord instance.
$discord = new Discord($argv[1], $argv[2]);
// Init the WebSocket instance.
$ws = new WebSocket($discord);

// We use EventEmitters to emit events. They are pretty much
// identical to the JavaScript/NodeJS implementation.
// 
// Here we are waiting for the WebSocket client to parse the READY frame. Once
// it has done that it will run the code in the closure.
$ws->on('ready', function ($discord) use ($ws) {
	// In here we can access any of the WebSocket events.
	// 
	// There is a list of event constants that you can
	// find here: https://teamreflex.github.io/DiscordPHP/classes/Discord.WebSockets.Event.html
	// 
	// We will echo to the console that the WebSocket is ready.
	echo "Discord WebSocket is ready!".PHP_EOL;

	// Here we will just log all messages.
	$ws->on(Event::MESSAGE_CREATE, function ($message, $discord, $newdiscord) {
		$reply = $message->timestamp->format('d/m/y H:i:s') . ' - '; // Format the message timestamp.
		$reply .= $message->full_channel->guild->name . ' - ';
		$reply .= $message->author->username . ' - '; // Add the message author's username onto the string.
		$reply .= $message->content; // Add the message content.

		echo $reply . PHP_EOL; // Finally, echo the message with a PHP end of line.
	});
});

// Now we will run the ReactPHP Event Loop!
$ws->run();