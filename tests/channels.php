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

startTest('Create Channel');

$createAttributes = [
    'name'     => 'testchannel',
    'type'     => Channel::TYPE_TEXT,
    'guild_id' => $baseGuild->id,
];

$channel = new Channel($createAttributes);

try {
    $channel->save();
} catch (\Exception $e) {
    fail($e);
}

checkAttributes($createAttributes, $channel);
pass();

startTest('Edit Channel');

$updateAttributes = [
    'name'     => 'newname',
    'topic'    => 'dank memes',
    'position' => rand(1, 10),
];

try {
    $channel->fill($updateAttributes);
    $channel->save();
} catch (\Exception $e) {
    fail($e);
}

checkAttributes($updateAttributes, $channel);
pass();

startTest('Create Invite');

try {
    $invite = $channel->createInvite();
} catch (\Exception $e) {
    fail($e);
}

pass();

startTest('Broadcasting Typing');

try {
    $channel->broadcastTyping();
} catch (\Exception $e) {
    fail($e);
}

pass();

startTest('Delete Channel');

try {
    $channel->delete();
} catch (\Exception $e) {
    fail($e);
}

$guild = $discord->guilds->get('id', $channel->guild_id);

if ($guild->channels->get('id', $channel->id) !== null) {
    fail('Deleting the channel did not work.');
}

pass();
