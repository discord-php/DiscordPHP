<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Discord\RPC\IpcClient;
use React\EventLoop\Loop;
use React\Promise\Deferred;

// Async example: connect using ReactPHP loop and use event-driven frame handling.
$clientId = getenv('DISCORD_APP_ID') ?: '192741864418312192';
$loop = Loop::get();
$ipc = new IpcClient($loop);

$ipc->connect($clientId)->then(function (IpcClient $client) use ($loop) {
    echo "Connected to IPC." . PHP_EOL;

    // Create a nonce to correlate the response for GET_GUILDS and wait for it.
    $nonce = uniqid('getg_', true);
    $response = new Deferred();
    $done = false;

    // Wait for the RPC READY/handshake response before sending GET_GUILDS
    $ready = new Deferred();

    $client->on('frame', function (int $opcode, int $len, string $payload) use ($response, $nonce, &$done, &$ready) {
        echo "Frame opcode={$opcode} len={$len}" . PHP_EOL;
        $decoded = json_decode($payload, true);
        if ($decoded === null) {
            echo "Failed to decode JSON payload: " . json_last_error_msg() . PHP_EOL;
            return;
        }

        // If this frame is the READY/handshake event from the RPC server, resolve the ready deferred.
        if (isset($decoded['evt']) && $decoded['evt'] === 'READY') {
            echo "RPC READY received" . PHP_EOL;
            $ready->resolve($decoded);
            // still print the READY payload
            print_r($decoded);
            return;
        }

        // If the frame contains the matching nonce, resolve the deferred so the example can finish.
        if (isset($decoded['nonce']) && $decoded['nonce'] === $nonce) {
            $done = true;
            $response->resolve($decoded);
            return;
        }

        // Otherwise, print other frames for debugging.
        print_r($decoded);
    });

    $client->on('error', function ($e) {
        echo "Client error: " . (is_object($e) && $e instanceof \Throwable ? $e->getMessage() : print_r($e, true)) . PHP_EOL;
    });

    $client->on('close', function () use ($loop, $response, &$done) {
        if (! $done) {
            $response->reject(new \RuntimeException('Connection closed before GET_GUILDS response'));
        }
        echo "Connection closed" . PHP_EOL;
        $loop->stop();
    });

    // Wait for READY, then send GET_GUILDS and wait for the matching response (10s timeout).
    $ready->promise()->then(function ($decoded) use ($client, $nonce, $loop, $response) {
        // send GET_GUILDS now that the RPC server is ready
        echo "Sending GET_GUILDS after READY..." . PHP_EOL;
        $client->sendCommand('GET_GUILDS', [], null, $nonce);

        $loop->addTimer(10.0, function () use ($response) {
            $response->reject(new \RuntimeException('Timeout waiting for GET_GUILDS response'));
        });
    }, function ($e) use ($loop) {
        echo "Timeout waiting for RPC READY: " . ($e instanceof \Throwable ? $e->getMessage() : (string) $e) . PHP_EOL;
        $loop->stop();
    });

    // If READY never comes within 5s, reject it.
    $loop->addTimer(5.0, function () use ($ready) {
        $ready->reject(new \RuntimeException('Timeout waiting for RPC READY'));
    });

    $response->promise()->then(function ($decoded) use ($loop, &$done) {
        $done = true;
        echo "Received GET_GUILDS response:" . PHP_EOL;
        print_r($decoded);
        $loop->stop();
    }, function ($e) use ($loop, &$done) {
        $done = true;
        echo "Error or timeout: " . ($e instanceof \Throwable ? $e->getMessage() : (string) $e) . PHP_EOL;
        $loop->stop();
    });
}, function ($e) use ($loop) {
    echo "Failed to connect to IPC: " . $e->getMessage() . PHP_EOL;
    $loop->stop();
});

$loop->run();
