'use strict';

// Transport only. No Discord framing, OAuth, payload parsing, logs or TCP listener.
const net = require('node:net');

function bridge({ port, secret, path, timeout }) {
    const local = net.createConnection({ host: '127.0.0.1', port });
    let pipe;
    let closed = false;
    const timer = setTimeout(close, timeout);
    function close() {
        if (closed) return;
        closed = true;
        clearTimeout(timer);
        local.destroy();
        if (pipe) pipe.destroy();
    }
    local.on('error', close);
    local.on('close', close);
    local.on('end', close);
    local.once('connect', () => {
        local.write(secret);
        // Pause until the native pipe is connected; no unbounded preconnect queue.
        local.pause();
        pipe = net.createConnection(path);
        pipe.on('error', close);
        pipe.on('close', close);
        pipe.on('end', close);
        pipe.once('connect', () => {
            clearTimeout(timer);
            local.write('1');
            // Node stream piping propagates pause/drain in both directions.
            local.pipe(pipe);
            pipe.pipe(local);
        });
    });
    return { close };
}

if (require.main === module) {
    const { DPHP_RPC_PORT: port, DPHP_RPC_SECRET: secret, DPHP_RPC_INDEX: index, DPHP_RPC_TIMEOUT: timeout } = process.env;
    if (process.platform !== 'win32' || !/^[0-9]+$/.test(port || '') || +port < 1 || +port > 65535 ||
        !/^[a-f0-9]{64}$/.test(secret || '') || !/^[0-9]$/.test(index || '') ||
        !/^[0-9]+$/.test(timeout || '') || +timeout < 1) {
        process.exitCode = 1;
    } else {
        // Normalize both accepted PHP path spellings to the canonical local Win32 pipe path.
        bridge({ port: +port, secret, path: `\\\\.\\pipe\\discord-ipc-${index}`, timeout: +timeout });
    }
}
module.exports = { bridge };
