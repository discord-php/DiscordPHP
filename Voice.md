## Voice

Voice in DiscordPHP has 2 main requirements:

- FFmpeg
- DCA (Discord Audio)

### Installations

#### FFmpeg

##### Windows

1. Download the build over at the [FFmpeg Builds](http://ffmpeg.zeranoe.com/builds/) page.
2. Install the build.

##### Mac

1. Get [Homebrew](http://brew.sh/).
2. Run `brew install ffmpeg`.

#### DCA

Instructions to install DCA can be found in the DCA repo's [readme](https://github.com/bwmarrin/dca).

**Note:** DCA must be installed in your path with the name `dca` or `ff2opus`.

### Usage

Check out `examples/voice.php` to see usage.