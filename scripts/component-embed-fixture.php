<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

// It constructs no Discord client and makes no network requests.
require __DIR__.'/../vendor/autoload.php';

use Discord\Builders\ComponentEmbedBuilder;

$content = in_array('--unicode', $argv, true)
    ? "# DiscordPHP 😀\nQuotes: \" & ' — </ScRiPt><script>literal text</script>"
    : "# DiscordPHP\nAn asynchronous PHP library for Discord.";
$image = 'https://discord.com/api/guilds/115233111977099271/widget.png?style=banner1';
$preview = ComponentEmbedBuilder::new([
    'type' => 17,
    'accent_color' => 0x5865F2,
    'components' => [
        ['type' => 10, 'content' => $content],
        ['type' => 12, 'items' => [['media' => ['url' => $image], 'description' => 'DiscordPHP community']]],
        ['type' => 1, 'components' => [['type' => 2, 'style' => 5, 'label' => 'Read the guide', 'url' => 'https://discord-php.github.io/DiscordPHP/']]],
    ],
]);

echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>DiscordPHP</title>';
echo '<meta property="og:title" content="DiscordPHP">';
echo '<meta property="og:description" content="An asynchronous PHP library for Discord.">';
echo '<meta property="og:url" content="https://discordphp.org/">';
echo '<meta property="og:type" content="website">';
echo '<meta property="og:site_name" content="DiscordPHP">';
echo '<meta property="og:image" content="'.htmlspecialchars($image, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">';
echo '<meta name="twitter:card" content="summary_large_image">';
echo $preview->toScript();
echo '</head><body><h1>DiscordPHP</h1></body></html>';
