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
use Discord\Voice\VoiceClient;
use Discord\WebSockets\Event;
use Discord\WebSockets\WebSocket;

// Includes the Composer autoload file
include '../vendor/autoload.php';

if ($argc != 3) {
    echo 'You must pass your Token into the cmdline. Example: php voice.php <token> <file-to-play>';
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
$ws->on('ready', function ($discord) use ($ws, $argv) {
    // In here we can access any of the WebSocket events.
    //
    // There is a list of event constants that you can
    // find here: https://teamreflex.github.io/DiscordPHP/classes/Discord.WebSockets.Event.html
    //
    // We will echo to the console that the WebSocket is ready.
    echo 'Discord WebSocket is ready!'.PHP_EOL;

    // We will now get the guild and channel to connect to.
    $guild = $discord->guilds->first();
    $channel = $guild->channels->get('type', 'voice');

    echo "Connecting to {$guild->name} {$channel->name}...\r\n";

    // And connect to the channel...
    $ws->joinVoiceChannel($channel)->then(function (VoiceClient $vc) use ($ws, $argv) {
        echo "Joined voice channel.\r\n";

        // Here we will set the frame size to 40ms. Note: Lower is better!
        //
        // The valid options are:
        // - 20ms
        // - 40ms
        // - 60ms
        //
        // The lower the better. Always start with 20ms and work your way up.
        // If you are experiencing 'buffering' while playing audio, try increase
        // the frame size.
        //
        // You do not have to set the frame size every time. It defaults to 20ms.
        // The Voice Client also sets the frame size itself based on ping.
        $vc->setFrameSize(40)->then(function () use ($vc, $ws, $argv) {
            // We can now play stuff!
            $vc->playFile($argv[2])->then(function () {
                echo "Finished playing song.\r\n";
            });

            // We will pause the song after 5 seconds and then unpause 5 seconds later.
            $ws->loop->addTimer(5, function () use ($vc, $ws) {
                $vc->pause()->then(function () use ($vc, $ws) {
                    echo "Paused.\r\n";

                    $ws->loop->addTimer(5, function () use ($vc) {
                        $vc->unpause()->then(function () {
                            echo "Unpaused.\r\n";
                        });
                    });
                });
            });
        });
    });
});

// Now we will run the ReactPHP Event Loop!
$ws->run();
