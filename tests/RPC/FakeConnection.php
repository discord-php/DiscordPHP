<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Evenement\EventEmitter;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

/** Offline duplex transport: no sockets, files, processes or Discord session. */
final class FakeRpcConnection extends EventEmitter implements ConnectionInterface
{
    public array $writes = [];
    public bool $writable = true;
    public bool $acceptMore = true;
    public int $closes = 0;

    public function write($data)
    {
        $this->writes[] = $data;

        return $this->acceptMore;
    }

    public function end($data = null)
    {
        if ($data !== null) {
            $this->write($data);
        }
        $this->close();
    }

    public function close()
    {
        ++$this->closes;
        $this->writable = false;
        $this->emit('close');
    }

    public function isReadable()
    {
        return $this->writable;
    }
    public function isWritable()
    {
        return $this->writable;
    }
    public function pause()
    {
    }
    public function resume()
    {
    }
    public function pipe(WritableStreamInterface $dest, array $options = [])
    {
        return $dest;
    }
    public function getRemoteAddress()
    {
        return null;
    }
    public function getLocalAddress()
    {
        return null;
    }

    public function receive(array $payload, int $opcode = 1): void
    {
        $this->emit('data', [\Discord\RPC\FrameDecoder::encode($opcode, json_encode($payload, JSON_THROW_ON_ERROR))]);
    }

    public function payload(int $index): array
    {
        return json_decode(substr($this->writes[$index], 8), true, 64, JSON_THROW_ON_ERROR);
    }
}
