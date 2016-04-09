## Voice

Voice in DiscordPHP has 3 main requirements:

- FFmpeg
- DCA (Packaged with DiscordPHP)
- libsodium-php

### Encryption

Since Discord will be removing `plain` voice at the end of April, DiscordPHP requires libsodium and libsodium-php to be installed to encrypt the audio packets.

#### Mac

1. Get [Homebrew](http://brew.sh/).
2. Install PHP with Homebrew if you haven't already.
3. `brew install libsodium`
4. `brew install homebrew/php/php**-libsodium`

#### Ubuntu

1. `wget https://github.com/jedisct1/libsodium/releases/download/1.0.10/libsodium-1.0.10.tar.gz`
2. `tar -xvzf libsodium-1.0.10.tar.gz && cd libsodium-1.0.10`
3. `./configure`
4. `make && sudo make install`

If you have PECL installed:

- `pecl install libsodium`

Otherwise:

1. `wget https://github.com/jedisct1/libsodium-php/archive/1.0.5.tar.gz`
2. `tar -xvzf 1.0.5.tar.gz && cd libsodium-php-1.0.5`
3. `phpize && ./configure`
4. `make && sudo make install`
5. `sudo sh -c 'echo "extension="$PWD"/modules/libsodium.so" >> $(php -r "echo php_ini_loaded_file(), PHP_EOL;")'`

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