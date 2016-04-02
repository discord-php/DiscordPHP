<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Parts\Channel\Channel;
use React\Promise\Deferred;

$deferred = new Deferred();

startTest('Create Channel');

$createAttributes = [
    'name'     => 'testchannel',
    'type'     => Channel::TYPE_TEXT,
];

$channel = $discord->partFactory->create(Channel::class, $createAttributes);

$baseGuild->channels->save($channel)->then(function ($channel) use ($baseGuild, $failPromise, $deferred) {
    checkAttributes($createAttributes, $channel);
    pass();

    startTest('Edit Channel');

    $updateAttributes = [
        'name'     => 'newname',
        'topic'    => 'dank memes',
        'position' => rand(1, 10),
    ];

    $channel->fill($updateAttributes);
    $baseGuild->channels->save($channel)->then(function ($channel) use ($baseGuild, $failPromise, $deferred) {
        checkAttributes($updateAttributes, $channel);
        pass();

        startTest('Create Invite');

        $channel->createInvite()->then(function ($invite) use ($channel, $baseGuild, $failPromise, $deferred) {
            pass();

            startTest('Broadcasting Typing');

            $channel->broadcastTyping()->then(function () use ($channel, $baseGuild, $failPromise, $deferred) {
                pass();

                startTest('Delete Channel');

                $baseGuild->channels->delete($channel)->then(function ($channel) use ($baseGuild, $failPromise, $deferred) {
                    if ($baseGuild->channels->get('id', $channel->id) !== null) {
                        fail('Deleting the channel did not work.');
                    }

                    pass();

                    require_once 'messages.php';
                }, $failPromise);
            }, $failPromise);
        }, $failPromise);
    }, $failPromise);
}, $failPromise);
