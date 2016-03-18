<?php

use Discord\Parts\Guild\Guild;

startTest('Create Guild');

$createAttributes = [
	'name' => 'DiscordPHP Unit Tests',
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
	'name' => 'DiscordPHP Unit Tests - Edited',
	'region' => Guild::REGION_SINGAPORE,
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