'use strict';
// Synthetic native pipe server. Never starts or authenticates with Discord.
const net = require('node:net');
const fs = require('node:fs');
const [path, readyFile, mode] = process.argv.slice(2);
const server = net.createServer(socket => {
    socket.on('error', () => {});
    if (mode === 'echo') {
        socket.pause();
        setTimeout(() => socket.pipe(socket), 100);
        return;
    }
    let buffer = Buffer.alloc(0);
    socket.on('data', bytes => {
        buffer = Buffer.concat([buffer, bytes]);
        while (buffer.length >= 8 && buffer.length >= 8 + buffer.readUInt32LE(4)) {
            const opcode = buffer.readUInt32LE(0);
            const length = buffer.readUInt32LE(4);
            const request = JSON.parse(buffer.subarray(8, 8 + length));
            buffer = buffer.subarray(8 + length);
            if (mode === 'silent') continue;
            const payload = Buffer.from(JSON.stringify(opcode === 0 ?
                { cmd: 'DISPATCH', evt: 'READY', data: { v: 1 } } :
                { cmd: request.cmd, nonce: request.nonce, data: { synthetic: true } }));
            const header = Buffer.alloc(8);
            header.writeUInt32LE(1, 0);
            header.writeUInt32LE(payload.length, 4);
            const frame = Buffer.concat([header, payload]);
            socket.write(frame.subarray(0, 3));
            setTimeout(() => socket.write(frame.subarray(3)), 10);
        }
    });
});
server.on('error', () => { process.exitCode = 1; });
server.listen(path, () => fs.writeFileSync(readyFile, 'ready'));
setTimeout(() => process.exit(0), 30000).unref();
