<?php

declare(strict_types=1);

namespace Discord\Voice\Client;

use Discord\Helpers\ByteBuffer\Buffer;
use Discord\WebSockets\Op;
use React\EventLoop\TimerInterface;
use React\Datagram\Socket;

use function Discord\logger;
use function Discord\loop;

/**
 * Handles the UDP connection & events for Discord voice.
 * This class manages the UDP socket for sending and receiving audio data,
 * handling heartbeats, and managing the voice connection state.
 *
 * @since 10.19.0
 */
final class UDP extends Socket
{
    /**
     * The Parent Voice WebSocket Client.
     */
    protected WS $ws;

    /**
     * Silence Frame Remain Count.
     */
    public int $silenceRemaining = 5;

    /**
     * The Opus Silence Frame.
     */
    public const string SILENCE_FRAME = "\0xF8\0xFF\0xFE";

    /**
     * The stream time of the last packet.
     */
    public int $streamTime = 0;

    /**
     * Current heartbeat timer.
     */
    public ?TimerInterface $heartbeat = null;

    /**
     * Heartbeat interval in milliseconds.
     * The interval at which the heartbeat is sent.
     */
    public ?int $hbInterval = null;

    /**
     * Heartbeat sequence number.
     * This is used to keep track of the heartbeat messages sent.
     */
    protected int $hbSequence = 0;

    /**
     * The IP address of the UDP server.
     */
    public string $ip;

    /**
     * The port of the UDP server.
     */
    public int $port;

    /**
     * The SSRC identifier.
     * This is used to identify the source of the audio stream.
     */
    public null|string|int $ssrc;

    /**
     * @param \React\EventLoop\LoopInterface $loop
     * @param resource $socket
     * @param null|Buffer$buffer
     * @param null|WS $ws
     */
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

    /**
     * Handles incoming messages from the UDP server.
     * This is where we handle the audio data received from the server.
     */
    public function handleMessages(string $secret): self
    {
        return $this->on('message', function (string $message) use ($secret) {

            if (strlen($message) <= 8) {
                return null;
            }

            if ($this->ws->vc->deaf) {
                return null;
            }

            return $this->ws->vc->handleAudioData(new Packet($message, key: $secret));
        });
    }

    /**
     * Handles the sending of the SSRC to the server.
     * This is necessary for the server to know which SSRC we are using.
     */
    public function handleSsrcSending(): self
    {
        $buffer = new Buffer(74);
        $buffer[1] = "\x01";
        $buffer[3] = "\x46";
        $buffer->writeUInt32BE($this->ws->vc->ssrc, 4);
        loop()->addTimer(0.1, fn () => $this->send($buffer->__toString()));

        return $this;
    }

    /**
     * Handles the heartbeat for the UDP client.
     * To keep the connection open and responsive.
     */
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
     * Decodes the first UDP message received from the server.
     * To discover which IP and port we should connect to.
     *
     * @see https://discord.com/developers/docs/topics/voice-connections#ip-discovery
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

    /**
     * Handles errors that occur during UDP communication.
     */
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

    /**
     * Closes the UDP client and cancels the heartbeat timer.
     */
    public function close(): void
    {
        if ($this->heartbeat) {
            loop()->cancelTimer($this->heartbeat);
            $this->heartbeat = null;
        }

        parent::close();
    }

    /**
     * Refreshes the silence frames.
     */
    public function refreshSilenceFrames(): void
    {
        if (! $this->ws->vc->paused) {
            // If the voice client is paused, we don't need to refresh the silence frames.
            return;
        }

        $this->silenceRemaining = 5;
    }
}
