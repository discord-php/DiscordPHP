Optional local RPC client
=========================

``Discord\RPC\IpcClient`` is an optional, low-level client for Discord's
local IPC protocol. Construct and connect it explicitly in a CLI application.
Creating a bot client does not start RPC, and RPC does not start a bot or connect
to the Gateway. It uses the ReactPHP event loop and returns promises for
asynchronous operations.

This foundation provides framing, a handshake, command correlation and raw event
delivery. It does not provide typed command payload builders or typed RPC domain
objects. RPC responses can differ from HTTP API objects; do not assume that a
raw RPC response satisfies a DiscordPHP Part's contract.

Transport and platforms
----------------------

On Unix-like systems, discovery considers ``discord-ipc-0`` through
``discord-ipc-9`` under each configured directory, in this order:
``XDG_RUNTIME_DIR``, ``TMPDIR``, ``TMP``, ``TEMP``, then ``/tmp``.
The default connector uses asynchronous Unix sockets.

Windows is unsupported by the default connector. Applications may inject a
``React\Socket\ConnectorInterface`` that asynchronously returns a
``React\Socket\ConnectionInterface`` for native paths such as
``\\?\pipe\discord-ipc-0``. No native Windows pipe adapter, TCP bridge or
process proxy is shipped. Deprecated RPC over WebSocket is outside this module.
Path discovery and an injected stream fixture do not establish that the module
works with an installed Discord client on any platform.

Connecting and sending commands
-------------------------------

Create the client on the loop owned by your CLI application. Register listeners
before connecting; the connection promise resolves only after ``READY``.

.. code-block:: php

    use Discord\RPC\IpcClient;
    use React\EventLoop\Loop;

    $loop = Loop::get();
    $rpc = new IpcClient($loop);
    $rpc->on('dispatch', function (string $event, $data, array $raw) {
        // Handle a raw RPC dispatch; validate the event's data in your application.
    });
    $rpc->on('error', function (\Throwable $error) {
        // Record an operational error without logging tokens or authorization codes.
    });

    $rpc->connect('YOUR_NUMERIC_APPLICATION_ID')->then(
        function (IpcClient $rpc) {
            // IPC is ready; authorization and authentication are separate steps.
        },
        function (\Throwable $error) {
            // Connection or handshake failed.
        }
    );
    $loop->run();

After authenticating with the necessary scopes and eligibility, use
``request($command, $args, $event, $timeout)``. It returns the complete raw
response envelope, including ``cmd``, ``nonce`` and ``data`` where present.
The library supplies the nonce and correlates replies. The caller owns each
command's arguments and response interpretation.

.. code-block:: php

    $rpc->request('GET_GUILD', ['guild_id' => $guildId])->then(
        function (array $response) {
            $guildData = $response['data'];
        }
    );

    $rpc->request('SUBSCRIBE', ['channel_id' => $channelId], 'MESSAGE_CREATE');
    $rpc->request('UNSUBSCRIBE', ['channel_id' => $channelId], 'MESSAGE_CREATE');

``SUBSCRIBE`` and ``UNSUBSCRIBE`` use the same command correlation mechanism.
There is no local subscription registry or automatic subscription replay.
Reconnect explicitly after a disconnect, then authenticate and restore any
subscriptions your application still needs. Pending commands are rejected on
disconnect. ``close()`` closes the session; ``sendPing($payload)`` sends a
protocol ping and does not constitute a platform health check.

Authorization and confidential token exchange
--------------------------------------------

``authorize(['rpc', 'identify'])`` sends ``AUTHORIZE`` and returns its raw
response. ``authenticate($accessToken)`` sends ``AUTHENTICATE``. These helpers
do not make an application eligible for restricted scopes. Discord documents
approval and tester restrictions; verify access with Discord for your own app.
A bot token is not an RPC OAuth2 access token.

``authorizeAndAuthenticate($scopes, $redirectUri, $exchange, $timeout)`` chains
authorization, an injected asynchronous token exchange, and authentication.
Only one such flow may be active per client; concurrent flows reject. Call
``close()`` when finished to release the owned connection and deadlines.
Implement ``Discord\RPC\TokenExchangeInterface``:

.. code-block:: php

    public function exchange(
        string $code,
        string $clientId,
        string $redirectUri
    ): \React\Promise\PromiseInterface;

The promise must resolve to a nonempty access-token string. Host the confidential
OAuth2 exchange securely in infrastructure you control. Never ship an
application client secret in a distributed desktop client. This module does not
implement the token endpoint, store tokens, refresh tokens, or ship a client
secret. Your exchange implementation owns transport, server authentication,
redirect validation and token handling. Keep authorization codes and access
tokens out of logs.

API reference
-------------

All timeout values below are seconds.

.. code-block:: php

    new IpcClient(?LoopInterface $loop = null, ?ConnectorInterface $connector = null, array $options = []);
    connect(string $clientId, int $version = 1): PromiseInterface;
    request(string $command, array $args = [], ?string $event = null, float $timeout = 15): PromiseInterface;
    authorize(array $scopes = ['rpc', 'identify'], float $timeout = 15): PromiseInterface;
    authenticate(string $accessToken, float $timeout = 15): PromiseInterface;
    authorizeAndAuthenticate(array $scopes, string $redirectUri, TokenExchangeInterface $exchange, float $timeout = 15): PromiseInterface;
    close(): void;
    sendPing(string $payload = ''): void;

Listeners receive ``ready`` (data, raw READY envelope), ``dispatch`` (event name,
data, raw envelope), ``error`` (``Throwable``), and ``close`` (nullable
``CloseException``). ``pong`` receives the payload bytes. ``READY`` and ``ERROR``
are handled by the lifecycle and error paths rather than ordinary ``dispatch``
notifications. Command failures reject the request promise; handle its
rejection even when you have installed an ``error`` listener.

The constructor accepts these resource and scheduling options:

.. list-table::
    :header-rows: 1

    * - Option
      - Default
      - Meaning
    * - ``paths``
      - ``IpcClient::getCandidatePaths()``
      - Ordered local Unix socket URIs or Discord native pipe paths.
    * - ``connect_timeout``
      - ``10.0``
      - Overall connection and READY deadline in seconds.
    * - ``max_frame_bytes``
      - ``1048576``
      - Maximum payload bytes per frame.
    * - ``max_buffer_bytes``
      - ``2097168``
      - Maximum buffered incoming bytes; at least frame limit plus eight.
    * - ``max_pending_requests``
      - ``128``
      - Maximum concurrent pending commands.
    * - ``max_queue_bytes``
      - ``4194304``
      - Maximum buffered outgoing bytes.
    * - ``frames_per_tick``
      - ``32``
      - Per-tick frame processing budget.

Unknown options and invalid limits are rejected. Request deadlines include
queue time. Cancelling an unsent request removes it from the queue; cancelling
a command already sent cannot undo its effect. The injected token exchange must
support cancellation so a deadline or disconnect can abort pending exchange
work. Use positive finite timeout values.

Coverage and verification boundaries
------------------------------------

``scripts/rpc-coverage.json`` inventories the documented command and event names.
The audit dated 2026-10-20 counts 18 client commands and 25 events, plus the
server ``DISPATCH`` envelope: 44 named entries. ``SELECT_TEXT_CHANNEL`` and
``ACTIVITY_INVITE`` are included. The inventory records raw envelope support,
convenience helpers, absent typed wrappers, and fixture evidence separately.
It is not a full-support percentage or an assertion that all commands work
against Discord.

Offline stream fixtures can verify framing, handshakes, correlation, timeouts,
disconnect behavior and dispatch forwarding. ``tests/RPC/IpcSocketTest.php`` also
exercises the default connector and real event loop against a temporary local
Unix socket server: path fallback, fragmented READY, binary PING/PONG,
out-of-order replies and shutdown when READY never arrives. These tests skip
where PHP has no Unix socket transport, including the Windows CI environment.
They cannot prove compatibility with an installed Discord client, native Windows
pipe support, user consent, app approval, scope eligibility, or command-specific
behavior on Discord. No live authentication or live command integration is claimed.

Sources
-------

The inventory was reconciled against the official references on 2026-10-20:

* `RPC <https://docs.discord.com/developers/topics/rpc>`_ for IPC, payloads,
  commands, events and authorization.
* `Opcodes and status codes <https://docs.discord.com/developers/topics/opcodes-and-status-codes#rpc>`_
  for RPC errors and close codes. Error and close code namespaces are distinct.
* `OAuth2 scopes <https://docs.discord.com/developers/topics/oauth2#oauth2-scopes>`_
  for scope names and approval restrictions.

The RPC ``RELATIONSHIP_UPDATE`` prose names ``relationships_read``, while the
OAuth2 scope table names ``relationships.read``. The inventory preserves this
source discrepancy; it does not silently infer which spelling the live RPC
server accepts. New projects should also assess Discord's recommended Social
SDK against their requirements.
