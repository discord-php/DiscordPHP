<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WSClient;

use Evenement\EventEmitterTrait;
use Evenement\EventEmitterInterface;
use Ratchet\ConnectionInterface;
use React\Stream\DuplexStreamInterface;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Ratchet\WebSocket\Version\RFC6455\Message;
use Ratchet\WebSocket\Version\RFC6455\Frame;

/**
 * Thanks to Chris Boden for the WebSocket client.
 */
class WebSocket implements EventEmitterInterface, ConnectionInterface
{
    use EventEmitterTrait;

    /**
     * The request headers sent to establish the connection.
     *
     * @var \Guzzle\Http\Message\Request
     */
    public $request;

    /**
     * The response headers received from the server to establish the connection.
     *
     * @var \Guzzle\Http\Message\Response
     */
    public $response;

    /**
     * @var \React\Stream\Stream
     */
    protected $_stream;

    /**
     * @var Ratchet\WebSocket\Version\RFC6455\Message
     */
    private $_message;

    /**
     * @var Ratchet\WebSocket\Version\RFC6455\Frame
     */
    private $_frame;

    public function __construct(DuplexStreamInterface $stream, Response $response, Request $request)
    {
        $this->_stream = $stream;
        $this->response = $response;
        $this->request = $request;

        $stream->on('data', function ($data) {
            $this->handleData($data);
        });

        $stream->on('end', function (DuplexStreamInterface $stream) {
            if (is_resource($stream->stream)) {
                stream_socket_shutdown($stream->stream, STREAM_SHUT_RDWR);
                stream_set_blocking($stream->stream, false);
            }
        });

        // $stream->on('close', function () {
        //     $this->emit('close', [$this]);
        // });

        $stream->on('error', function ($error) {
            $this->emit('error', [$error, $this]);
        });
    }

    public function send($msg)
    {
        if ($msg instanceof Frame) {
            $frame = $msg;
        } else {
            $frame = new Frame($msg);
        }
        $frame->maskPayload($frame->generateMaskingKey());

        $this->_stream->write($frame->getContents());
    }

    public function close($code = 1000)
    {
        $frame = new Frame(pack('n', $code), true, Frame::OP_CLOSE);

        $this->_stream->write($frame->getContents());
        $this->_stream->end();
    }

    private function handleData($data)
    {
        if (0 === strlen($data)) {
            return;
        }

        if (! $this->_message) {
            $this->_message = new Message();
        }
        if (! $this->_frame) {
            $frame = new Frame();
        } else {
            $frame = $this->_frame;
        }

        $frame->addBuffer($data);

        if ($frame->isCoalesced()) {
            $opcode = $frame->getOpcode();

            if ($opcode > 2) {
                if ($frame->getPayloadLength() > 125 || ! $frame->isFinal()) {
                    $this->close(Frame::CLOSE_PROTOCOL);

                    return;
                }

                switch ($opcode) {
                    case Frame::OP_CLOSE:
                        $this->close($frame->getPayload());
                        
                        $close_op = unpack('n', (binary) $frame->getPayload());
                        $close_op = reset($close_op);

                        if (strlen($frame->getPayload()) > 2) {
                            $reason = substr($frame->getPayload(), 2, strlen($frame->getPayload()));
                        } else {
                            $reason = "";
                        }

                        $this->emit('close', [$close_op, $reason]);

                        return;
                    case Frame::OP_PING:
                        $this->send(new Frame($frame->getPayload(), true, Frame::OP_PONG));
                        break;
                    case Frame::OP_PONG:
                        $this->emit('pong', [$frame, $this]);
                        break;
                    default:
                        $this->close($frame->getPayload());
                        
                        $close_op = unpack('n', (binary) $frame->getPayload());
                        $close_op = reset($close_op);

                        if (strlen($frame->getPayload()) > 2) {
                            $reason = substr($frame->getPayload(), 2, strlen($frame->getPayload()));
                        } else {
                            $reason = "";
                        }

                        $this->emit('close', [$close_op, $reason]);

                        return;
                }
            }

            $overflow = $frame->extractOverflow();

            $this->_frame = null;

            // if this is a control frame, then we aren't going to be coalescing
            // any message, just handle overflowing stuff now and return
            if ($opcode > 2) {
                $this->handleData($overflow);

                return;
            } else {
                $this->_message->addFrame($frame);
            }
        } else {
            $this->_frame = $frame;
        }

        if (! $this->_message->isCoalesced()) {
            if (isset($overflow)) {
                $this->handleData($overflow);
            }

            return;
        }

        $message = $this->_message->getPayload();

        if ($this->_message->getOpcode() == Frame::OP_BINARY) {
            $message = zlib_decode($message);
        }

        $this->_frame = $this->_message = null;

        $this->emit('message', [$message, $this]);

        $this->handleData($overflow);
    }
}
