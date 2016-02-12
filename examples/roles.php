<?php

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\WebSocket;

// Includes the Composer autoload file
include '../vendor/autoload.php';

if ($argc < 3) {
	echo "You must pass your Email and Password into the cmdline. Example: php roles.php <email> <password>";
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

	// Here we will find the guild.
	$guild = $discord->guilds->first();
	// And now the user to change.
	$member = $guild->members->first();
	// And the role to remove.
	$role = $member->roles->first();

	// Now we remove the role!
	$member->removeRole($role);
	$member->save(); // Remember: You MUST save after changing roles.

	echo "{$member->username} had {$role->name} removed on {$guild->name}.".PHP_EOL;
});

// Now we will run the ReactPHP Event Loop!
$ws->run();