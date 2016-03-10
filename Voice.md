## Voice

Voice in DiscordPHP has 2 main requirements:

- FFmpeg
- DCA (Packaged with DiscordPHP)

### Encryption

Currently, encryption is only enabled if you have `libsodium` as well as `libsodium-php` installed. If you don't, your audio will be unencrypted.

Unencrypted audio will be removed in the near future. It will throw an exception if it is not installed.

### Installations

#### FFmpeg

##### Windows

1. Download the build over at the [FFmpeg Builds](http://ffmpeg.zeranoe.com/builds/) page.
2. Install the build.

##### Mac

1. Get [Homebrew](http://brew.sh/).
2. Run `brew install ffmpeg`.

#### DCA

The latest DCA version is packaged with DiscordPHP. It is reccomended to use this version and not install another.

Instructions to install DCA can be found in the DCA repo's [readme](https://github.com/bwmarrin/dca).

**Note:** DCA must be installed in your path with the name `dca` or `ff2opus`.

### Usage

Check out `examples/voice.php` to see usage.