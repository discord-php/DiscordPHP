'use strict';
const { test } = require('node:test');
const assert = require('node:assert/strict');
const net = require('node:net');
const path = require('node:path');
const crypto = require('node:crypto');
const { once } = require('node:events');
const { bridge } = require('../../src/Discord/RPC/bridge/windows-pipe.cjs');

async function setup(t, { echo = true, timeout = 1000, missing = false } = {}) {
    const id = crypto.randomBytes(8).toString('hex');
    const pipePath = process.platform === 'win32' ? `\\\\.\\pipe\\dphp-test-${id}` : path.join(process.cwd(), `dphp-${id}.sock`);
    const sockets = new Set();
    const track = socket => { sockets.add(socket); socket.on('error', () => {}); socket.on('close', () => sockets.delete(socket)); };
    const pipeServer = net.createServer(socket => {
        track(socket);
        if (echo) {
            socket.pause();
            setTimeout(() => socket.pipe(socket), 50);
        }
    });
    const localServer = net.createServer(track);
    t.after(() => { for (const socket of sockets) socket.destroy(); pipeServer.close(); localServer.close(); });
    if (!missing) {
        pipeServer.listen(pipePath);
        await once(pipeServer, 'listening');
    }
    localServer.listen(0, '127.0.0.1');
    await once(localServer, 'listening');
    assert.equal(localServer.address().address, '127.0.0.1');
    const incoming = once(localServer, 'connection');
    const secret = crypto.randomBytes(32).toString('hex');
    const helper = bridge({ port: localServer.address().port, secret, path: pipePath, timeout });
    t.after(helper.close);
    const [socket] = await incoming;
    return { socket, secret, helper, pipeServer };
}

test('authenticated preamble, native IPC, backpressure and binary integrity', { timeout: 5000 }, async t => {
    const { socket, secret } = await setup(t);
    let buffer = Buffer.alloc(0);
    const preamble = new Promise(resolve => {
        function data(bytes) {
            buffer = Buffer.concat([buffer, bytes]);
            if (buffer.length >= 65) { socket.off('data', data); resolve(); }
        }
        socket.on('data', data);
    });
    await preamble;
    assert.equal(buffer.toString(), secret + '1');
    const bytes = crypto.randomBytes(8 * 1024 * 1024);
    const chunks = [];
    let size = 0;
    const received = new Promise(resolve => socket.on('data', chunk => {
        chunks.push(chunk); size += chunk.length;
        if (size === bytes.length) resolve(Buffer.concat(chunks));
    }));
    // Windows may accept this entire write immediately; wait for drain only if requested.
    if (!socket.write(bytes)) await once(socket, 'drain');
    assert.deepEqual(await received, bytes);
});

test('missing native pipe closes without reporting transport ready', { timeout: 3000 }, async t => {
    const { socket, secret } = await setup(t, { missing: true });
    let bytes = '';
    socket.on('data', chunk => { bytes += chunk; });
    await once(socket, 'close');
    assert.equal(bytes, secret);
});

test('closing local socket releases native peer', { timeout: 3000 }, async t => {
    const { socket, pipeServer } = await setup(t);
    const [peer] = await once(pipeServer, 'connection');
    const closed = once(peer, 'close');
    socket.destroy();
    await closed;
});
