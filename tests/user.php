<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

startTest('Update Username');

$original          = $discord->username;
$discord->username = 'TestingUsernameUpdate';

try {
    $discord->save();
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'You are changing your username too fast.') !== false) {
        write('! Username changes were rate-limited.');
    } else {
        fail($e);
    }
}

if ($discord->username !== 'TestingUsernameUpdate') {
    fail('The username was not updated successfully.');
}

pass();

$discord->username = $original;

try {
    $discord->save();
} catch (\Exception $e) {
}

startTest('Update Avatar');

$discord->setAvatar(__DIR__.'/testimg.jpg');

try {
    $discord->save();
} catch (\Exception $e) {
    fail($e);
}

pass();
