---
name: voice-subsystem-keeper
description: >-
  Work with DiscordPHP's voice subsystem — voice gateway protocol opcodes,
  encryption (VoiceGroupCrypto), voice packets, audio streaming, and Discord.php
  voice integration. Use when touching Voice/*, joinVoiceChannel, or voice
  encryption/packet logic.
---

# Skill: voice-subsystem-keeper

Use this skill when work touches `src/Discord/Voice/*`, `Discord::joinVoiceChannel()`, voice event handlers in `Discord.php`, or anything involving audio encryption and packets.

## Architecture overview

Voice support is split across three locations. Understand the boundary before touching any of them:

| Location | What lives here |
| --- | --- |
| `src/Discord/Voice/*` | Internal protocol types: opcodes (Hello, Ready, Speaking), session description, voice packets, encryption classes and traits. These are pure data/crypto — no audio I/O. |
| `src/Discord/Discord.php` | Runtime integration: `joinVoiceChannel()`, voice state update handlers, voice server update handlers. This is where the external voice client is wired to gateway events. |
| `discord-php-helpers/voice` (external package) | `Manager` and `VoiceClient`: actual audio I/O, Opus encoding, UDP transport, stream management. DiscordPHP delegates audio work here. |

**Rule:** do not blur these boundaries. Protocol types belong in `Voice/*`, audio I/O belongs in the external package, and wiring belongs in `Discord.php`.

## Read in this order

1. `src/Discord/Voice/VoiceGroupCrypto.php` — encryption/decryption base class
2. `src/Discord/Voice/VoiceGroupCryptoTrait.php` — mixin providing group-based AEAD crypto
3. `src/Discord/Voice/VoiceGroupCryptoInterface.php` — contract
4. `src/Discord/Voice/VoicePacket.php` — encrypted RTP packet encapsulation
5. `src/Discord/Voice/SessionDescription.php` — session key and mode negotiation
6. `src/Discord/Voice/Speaking.php`, `src/Discord/Voice/Hello.php`, `src/Discord/Voice/Ready.php`, `src/Discord/Voice/Resumed.php` — voice gateway opcodes
7. `src/Discord/Voice/Platform.php`, `src/Discord/Voice/Region.php` — enum helpers
8. `src/Discord/Discord.php` — search for `joinVoiceChannel`, `VOICE_STATE_UPDATE`, `VOICE_SERVER_UPDATE`
9. `src/Discord/Helpers/Buffer.php` — writable stream for audio buffering (extends EventEmitter)

## Core concepts

### Voice gateway protocol

Discord voice uses a separate WebSocket gateway from the main gateway. The handshake sequence is:

1. `Hello` — server sends heartbeat interval
2. `Identify` — client sends token + session
3. `Ready` — server sends UDP endpoint + SSRC
4. `Select Protocol` — client sends chosen encryption mode
5. `Session Description` — server sends secret key
6. `Speaking` — sent before/after transmitting audio

The classes in `src/Discord/Voice/` model these protocol steps as typed value objects.

### Encryption

`VoiceGroupCrypto` provides AEAD encryption for RTP packets. It depends on libsodium (`ext-sodium`). The trait `VoiceGroupCryptoTrait` provides the implementation; concrete classes select the cipher mode (e.g., `aead_xchacha20poly1305_ietf`).

- `LibSodiumNotFoundException` is thrown at runtime if the extension is absent — do not suppress it
- Cipher mode is negotiated via `SessionDescription`

### VoicePacket

`VoicePacket` encapsulates an encrypted RTP packet:
- SSRC identifies the audio source
- Sequence number and timestamp are required for RTP ordering
- The packet is encrypted before transmission using the session secret key

### Audio I/O (external package)

`discord-php-helpers/voice` owns all audio work:
- Opus codec encoding/decoding
- UDP socket management
- OGG/Opus stream handling
- FFmpeg process integration

**Do not replicate any of this in `src/Discord/Voice/`.** If you need to add audio capability, contribute to the external package or wrap it.

### Old* files

`OldVoiceClient.php`, `OldBuffer.php`, `OldOggStream.php`, `OldOggPage.php`, `OldOpusHead.php`, `OldOpusTags.php`, `OldReceiveStream.php` are legacy implementations. They are preserved for compatibility only.

**Do not extend, copy patterns from, or add new features to any `Old*` class.** Fix bugs in them only when the fix is isolated and does not require architectural change.

## Companion surfaces

When touching voice code, also inspect:

| Touching | Also inspect |
| --- | --- |
| `VoiceGroupCrypto` or crypto mode | `SessionDescription`, `VoicePacket`, `VoiceGroupCryptoInterface`, `LibSodiumNotFoundException` |
| `Speaking` or voice gateway opcode | `Hello`, `Ready`, `Resumed`, `SessionDescription` — full handshake chain |
| `Discord.php` voice handlers | Voice gateway opcodes, `Buffer`, external voice package `Manager` |
| `Buffer.php` | `Multipart.php` (similar streaming pattern), external voice package stream classes |
| Any new voice encryption mode | `VoiceGroupCryptoInterface`, crypto trait, `SessionDescription` mode list |

## Playbook: adding a new voice encryption mode

1. Add the mode constant to `SessionDescription`.
2. Implement the mode in a class using `VoiceGroupCryptoTrait` or extending `VoiceGroupCrypto`.
3. Register the mode in the external voice package's cipher negotiation if needed.
4. Update `VoiceGroupCryptoInterface` if the contract changes.
5. Verify libsodium function availability — throw `LibSodiumNotFoundException` if missing.
6. Add tests for encrypt/decrypt round-trip.

## Playbook: adding a voice gateway opcode

1. Create a typed value class under `src/Discord/Voice/` mirroring the Discord voice gateway docs.
2. Wire the opcode handler in `Discord.php` (find the voice WebSocket message handler).
3. Document the opcode sequence in the class docblock.
4. Do not put audio I/O logic in the opcode class — keep it as a typed payload.

## Design tripwires

- Adding audio codec, UDP, or FFmpeg logic inside `src/Discord/Voice/` — that belongs in the external voice package
- Extending any `Old*` class for new features
- Skipping libsodium availability check before using sodium functions
- Hard-coding a cipher mode instead of reading it from `SessionDescription`
- Blocking I/O inside voice packet or stream handlers — everything must be async/Promise-based
- Catching `LibSodiumNotFoundException` silently instead of surfacing it to the caller

## Reference files

- `src/Discord/Voice/VoiceGroupCrypto.php` — encryption base
- `src/Discord/Voice/VoicePacket.php` — RTP packet wrapper
- `src/Discord/Voice/SessionDescription.php` — session key/mode
- `src/Discord/Voice/Speaking.php` — voice speaking opcode
- `src/Discord/Helpers/Buffer.php` — writable stream helper
- `src/Discord/Exceptions/LibSodiumNotFoundException.php` — crypto dependency guard
- `src/Discord/Exceptions/OpusNotFoundException.php` — codec dependency guard
- `src/Discord/Exceptions/FFmpegNotFoundException.php` — audio tool dependency guard
