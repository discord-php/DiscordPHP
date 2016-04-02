<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Parts\Guild\Guild;

startTest('Create Guild');

$createAttributes = [
    'name'   => 'DiscordPHP Unit Tests',
    'region' => Guild::REGION_SYDNEY,
];

$guild = new Guild($createAttributes);

try {
    $guild->save();
} catch (\Exception $e) {
    fail($e);
}

checkAttributes($createAttributes, $guild);

pass();

startTest('Edit Guild');

$updateAttributes = [
    'name'               => 'DiscordPHP Unit Tests - Edited',
    'region'             => Guild::REGION_SINGAPORE,
    'verification_level' => Guild::LEVEL_TABLEFLIP,
];
$guild->fill($updateAttributes);

try {
    $guild->save();
} catch (\Exception $e) {
    fail($e);
}

checkAttributes($updateAttributes, $guild);

pass();
