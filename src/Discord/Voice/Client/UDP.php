<?php

namespace Discord\Voice\Client;

use Discord\Helpers\ByteBuffer\Buffer;
use Discord\Voice\VoiceClient;
use Discord\WebSockets\Op;
use function Discord\logger;
use function Discord\loop;
use React\Datagram\Socket;

final class UDP extends Socket
{
    protected WS $ws;

    public function __construct($loop, $socket, $buffer = null, ?WS $ws = null)
    {
        parent::__construct($loop, $socket, $buffer);

        if ($ws !== null) {
            $this->ws = $ws;
        }
    }

    public function handleMessages(VoiceClient $vc, $secret): self
    {
        return $this->on('message', static fn (string $message) => $vc->handleAudioData(
            new Packet($message, key: $secret)
        ));
    }

    public function handleSsrcSending(): self
    {
        $buffer = new Buffer(74);
        $buffer[1] = "\x01";
        $buffer[3] = "\x46";
        $buffer->writeUInt32BE($this->ws->vc->ssrc, 4);
        loop()->addTimer(0.1, fn () => $this->ws->vc->client->send($buffer->__toString()));

        return $this;
    }

    public function handleHeartbeat(): self
    {
        $this->ws->vc->udpHeartbeat = loop()->addPeriodicTimer($this->ws->vc->heartbeatInterval / 1000, function (): void {
            $buffer = new Buffer(9);
            $buffer[0] = 0xC9;
            $buffer->writeUInt64LE($this->ws->vc->heartbeatSeq, 1);
            ++$this->ws->vc->heartbeatSeq;

            $this->ws->vc->client->send($buffer->__toString());
            $this->ws->vc->emit('udp-heartbeat', []);

            logger()->debug('sent UDP heartbeat');
        });

        return $this;
    }

    public function decodeOnce(): self
    {
        return $this->once('message', function (string $message) {
            /**
             * Unpacks the message into an array.
             *
             * C2 (unsigned char)   | Type      | 2 bytes   | Values 0x1 and 0x2 indicate request and response, respectively
             * n (unsigned short)   | Length    | 2 bytes   | Length of the following data
             * I (unsigned int)     | SSRC      | 4 bytes   | The SSRC of the sender
             * A64 (string)         | Address   | 64 bytes  | The IP address of the sender
             * n (unsigned short)   | Port      | 2 bytes   | The port of the sender
             *
             * @see https://discord.com/developers/docs/topics/voice-connections#ip-discovery
             * @see https://www.php.net/manual/en/function.unpack.php
             * @see https://www.php.net/manual/en/function.pack.php For the formats
             */
            $unpackedMessageArray = \unpack("C2Type/nLength/ISSRC/A64Address/nPort", $message);

            $this->ws->vc->ssrc = $unpackedMessageArray['SSRC'];
            $ip = $unpackedMessageArray['Address'];
            $port = $unpackedMessageArray['Port'];

            logger()->debug('received our IP and port', ['ip' => $ip, 'port' => $port]);

            $this->ws->send([
                'op' => Op::VOICE_SELECT_PROTO,
                'd' => [
                    'protocol' => 'udp',
                    'data' => [
                        'address' => $ip,
                        'port' => $port,
                        'mode' => $this->ws->mode,
                    ],
                ],
            ]);
        });
    }

    public function handleErrors(): self
    {
        return $this->on('error', function (\Throwable $e): void {
            logger()->error('UDP error', ['e' => $e->getMessage()]);
            $this->ws->vc->emit('udp-error', [$e]);
        });
    }
}
