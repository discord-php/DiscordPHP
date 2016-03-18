<?php

$channel = $baseGuild->channels->getAll('type', 'text')->first();

startTest('Sending Message');

$expected = [
	'content' => 'testing sending message',
];

try {
	$message = $channel->sendMessage('testing sending message');
	$tts = $channel->sendMessage('testing sending message', true);
	// $everyone = $channel->sendMessage('@everyone unit tests');
	$file = $channel->sendFile(__DIR__.'/testimg.jpg', 'testimg.jpg');
} catch (\Exception $e) {
	fail($e);
}

checkAttributes($expected, $message);
checkAttributes($expected + ['tts' => true], $tts);
// checkAttributes($expected + ['mention_everyone' => true, 'content' => '@everyone unit tests'], $everyone);

if (! isset($file['attachments'])) {
	fail('The image was not attached.');
}

pass();

startTest('Editing Message');

$expected['content'] = 'testing editing';

try {
	$message->content = $expected['content'];
	$message->save();
} catch (\Exception $e) {
	fail($e);
}

checkAttributes($expected, $message);

pass();

startTest('Deleting Message');

try {
	$message->delete();
} catch (\Exception $e) {
	fail($e);
}

$loop->addTimer(2, function () use ($channel, $message) {
	if ($channel->messages->get('id', $message->id) !== null) {
		fail('Deleting the message did not work.');
	}

	pass();
});

startTest('Send PM');

try {
	$testUser->sendMessage("Test Suite!");
} catch (\Exception $e) {
	fail($e);
}

pass();