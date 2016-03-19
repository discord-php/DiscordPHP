<?php

startTest('Update Username');

$original = $discord->username;
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
} catch (\Exception $e) {}

startTest('Update Avatar');

$discord->setAvatar(__DIR__.'/testimg.jpg');

try {
	$discord->save();
} catch (\Exception $e) {
	fail($e);
}

pass();
