<?php

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Field;
use Discord\Parts\Embed\Footer;
use Discord\Parts\Embed\Image;
use Discord\Parts\Embed\Author;

// Create a $discord BOT
$discord = new Discord([
    'token' => '',
]);

$discord->on('ready', function (Discord $discord) {

    $discord->on('message', function (Message $message, Discord $discord) {
      $prefix = "sg!";
        if (preg_match('/^'.$prefix.'p (.*)/ui', $message->content, $matches) ||preg_match('/^'.$prefix.'ping (.*)/ui', $message->content, $matches) && ! $message->author->bot) {
  $iptoping = gethostbyname($matches[1]);  
$start = microtime_float(); $fp = fsockopen($iptoping, 80, $errno, $errstr, 30); $end = microtime_float(); $ms = ($end - $start) * 1000;
if ($ms < 25) {
  $pingsymobl = "<:connectiongreat:937358383139397672> "; // Great connection
}
if ($ms > 25) {
  $pingsymobl = "<:slowconnection:937358380853522482> "; // Slow connection
}
if ($ms > 100) {
  $pingsymobl = "<:badconnection:937358380799000597> "; // Bad connection
}
if ($ms > 1000) {
  $pingsymobl = "<:noconnection:937358381553971200> "; // Allmost no connection
}
if ($ms > 2000) {
  $pingsymobl = "<:none:937358380719300608> "; // No connection
}

    $embed = new Embed($discord);
            $embed->setType(Embed::TYPE_RICH)
            ->setColor("00FF00")
            ->setDescription($pingsymobl.' '.number_format((float)$ms, 2, '.', '').'ms');
        $message->channel->sendEmbed($embed);
} else if ($message->content == 'sg!ping' || $message->content == 'sg!p' && ! $message->author->bot) {
  $embed = new Embed($discord);
            $embed->setType(Embed::TYPE_RICH)
            ->setColor("FF0000")
              ->setDescription("You need to provide an ip to ping!");
        $message->channel->sendEmbed($embed);
}
    });

});

$discord->run();
?>
