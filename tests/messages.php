<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

$channel = $baseGuild->channels->getAll('type', 'text')->first();

startTest('Sending Message');

$expected = [
    'content' => 'testing sending message',
];

$channel->sendMessage('testing sending message')->then(function ($message) use ($channel, $baseGuild, $failPromise, $expected) {
    checkAttributes($expected, $message);

    $channel->sendMessage('testing tts message', true)->then(function ($message) use ($channel, $baseGuild, $failPromise) {
        checkAttributes($expected + ['tts' => true], $tts);

        $channel->sendFile(__DIR__.'/testimg.jpg', 'testimg.jpg')->then(function ($message) use ($channel, $baseGuild, $failPromise) {
            if (!isset($message['attachments'])) {
                fail('The image was not attached.');
            }

            pass();

            startTest('Editing Message');

            $expected = [
                'content' => 'testing editing',
            ];

            $message->fill($expected);
            $channel->messages->save($message)->then(function ($message) use ($channel, $baseGuild, $failPromise, $expected) {
                checkAttributes($expected, $message);

                pass();

                startTest('Deleting Message');

                $channel->messages->delete($message)->then(function ($message) use ($channel, $baseGuild, $failPromise) {
                    pass();

                    startTest('Send PM');

                    $testUser = $baseGuild->members->get('id', getenv('DISCORD_TESTING_PM'));
                    $testUser->sendMessage('Test Suite!')->then(function ($e) use ($channel, $baseGuild, $failPromise) {
                        pass();

                        require_once 'roles.php';
                    }, $failPromise);
                }, $failPromise);
            }, $failPromise);
        }, $failPromise);
    }, $failPromise);
}, $failPromise);
