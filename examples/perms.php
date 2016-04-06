<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Discord;
use Discord\Parts\Permissions\ChannelPermission;
use Discord\Parts\Permissions\RolePermission;
use Discord\WebSockets\Event;
use Discord\WebSockets\WebSocket;

// Includes the Composer autoload file
include '../vendor/autoload.php';

if ($argc != 2) {
    echo 'You must pass your Token into the cmdline. Example: php perms.php <token>';
    die(1);
}

// Init the Discord instance.
$discord = new Discord(['token' => $argv[1]]);
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
    echo 'Discord WebSocket is ready!'.PHP_EOL;

    // Let's get the role to change!
    $guild = $discord->guilds->first();
    $role = $guild->roles->first();

    echo "Changing roles for {$guild->name} {$role->name}".PHP_EOL;

    // Now we will create our Permission instance.
    $allow = new RolePermission();

    // Let's enable:
    // - Create Instant Invite
    // - Manage Channels
    $allow->create_instant_invite = true;
    $allow->manage_channels = true;

    // And finally, update the role.
    $role->perms = $allow;
    $role->save();

    // Let's change a channels perms now!
    $channel = $guild->channels->first();

    // Now we will create our Permission instances.
    $allow = new ChannelPermission();
    $deny = new ChannelPermission();

    // Let's enable:
    // - Manage Permissions
    // - Manage Messages
    //
    // Let's disable:
    // - Read Message History
    // - Embed Links
    $allow->manage_permissions = true;
    $allow->manage_messages = true;

    $deny->read_message_history = true;
    $deny->embed_links = true;

    // Now let's set it on our channel.
    //
    // We will set it for the role that we obtained above.
    $channel->setPermissions($role, $allow, $deny);

    // Done!
    echo 'Saved permissions.'.PHP_EOL;
});

// Now we will run the ReactPHP Event Loop!
$ws->run();
