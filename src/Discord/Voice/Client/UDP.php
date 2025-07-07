<?php

declare(strict_types=1);

namespace Discord\Voice\Client;

use Discord\Helpers\ByteBuffer\Buffer;
use Discord\WebSockets\Op;
use React\EventLoop\TimerInterface;
use React\Datagram\Socket;

use function Discord\logger;
use function Discord\loop;

final class UDP extends Socket
{
    /**
     * The Parent Voice WebSocket Client.
     *
     * @var WS
     */
    protected WS $ws;

    /**
     * Silence Frame Remain Count.
     *
     * @var int Amount of silence frames remaining.
     */
    public int $silenceRemaining = 5;

    /**
     * The Opus Silence Frame.
     *
     * @var string The silence frame.
     */
    public const string SILENCE_FRAME = "\0xF8\0xFF\0xFE";

    /**
     * The stream time of the last packet.
     *
     * @var int The time we sent the last packet.
     */
    public int $streamTime = 0;

    public ?TimerInterface $heartbeat;

    public $hbInterval;

    protected int $hbSequence = 0;

    public string $ip;

    public int $port;

    public int $ssrc;

    public function __construct($loop, $socket, $buffer = null, ?WS $ws = null)
    {
        parent::__construct($loop, $socket, $buffer);

        if ($ws !== null) {
            $this->ws = $ws;

            if (null === $this->hbInterval) {
                // Set the heartbeat interval to the default value if not set.
                $this->hbInterval = $this->ws->vc->heartbeatInterval;
            }
        }
    }

    public function handleMessages(string $secret): self
    {
        return $this->on('message', function (string $message) use ($secret) {

            if (strlen($message) <= 8) {
                return null;
            }

            return $this->ws->vc->handleAudioData(new Packet($message, key: $secret));
        });
    }

    public function handleSsrcSending(): self
    {
        $buffer = new Buffer(74);
        $buffer[1] = "\x01";
        $buffer[3] = "\x46";
        $buffer->writeUInt32BE($this->ws->vc->ssrc, 4);
        loop()->addTimer(0.1, fn () => $this->send($buffer->__toString()));

        return $this;
    }

    public function handleHeartbeat(): self
    {
        if (empty($this->hbInterval)) {
            $this->hbInterval = $this->ws->vc->heartbeatInterval;
        }

        if (null === loop()) {
            logger()->error('No event loop found. Cannot handle heartbeat.');
            return $this;
        }

        $this->heartbeat = loop()->addPeriodicTimer(
            $this->hbInterval / 1000,
            function (): void {
                $buffer = new Buffer(9);
                $buffer[0] = 0xC9;
                $buffer->writeUInt64LE($this->hbSequence, 1);
                ++$this->hbSequence;

                $this->send($buffer->__toString());
                $this->ws->vc->emit('udp-heartbeat', []);

                logger()->debug('sent UDP heartbeat');
            }
        );

        return $this;
    }

    /**
     * Decodes the UDP message once.
     * @see https://discord.com/developers/docs/topics/voice-connections#ip-discovery
     * @return UDP
     */
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

    /**
     * Insert 5 frames of silence.
     *
     * @link https://discord.com/developers/docs/topics/voice-connections#voice-data-interpolation
     */
    public function insertSilence(): void
    {
        while (--$this->silenceRemaining > 0) {
            $this->sendBuffer(self::SILENCE_FRAME);
        }
    }

    /**
     * Sends a buffer to the UDP socket.
     *
     * @param string $data The data to send to the UDP server.
     */
    public function sendBuffer(string $data): void
    {
        if (! $this->ws->vc->ready) {
            return;
        }

        $packet = new Packet(
            $data,
            $this->ws->vc->ssrc,
            $this->ws->vc->seq,
            $this->ws->vc->timestamp,
            true,
            $this->ws->secretKey,
        );
        $this->send($packet->__toString());

        $this->streamTime = (int) microtime(true);

        $this->ws->vc->emit('packet-sent', [$packet]);
    }

    public function close(): void
    {
        if ($this->heartbeat) {
            loop()->cancelTimer($this->heartbeat);
            $this->heartbeat = null;
        }

        parent::close();
    }

    public function refreshSilenceFrames(): void
    {
        if (! $this->ws->vc->paused) {
            // If the voice client is paused, we don't need to refresh the silence frames.
            return;
        }

        $this->silenceRemaining = 5;
    }
}
