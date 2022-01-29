
<?php

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;

$discord = new Discord([
    'token' => '',
]);

$discord->on('ready', function (Discord $discord) {

    $discord->on('message', function (Message $message, Discord $discord) {
      $prefix = "yourprefix";
        if (preg_match('/^'.$prefix.'!cmdgen (.*)/ui', $message->content, $matches) && in_array($message->author->id, $cmdgenaccess) && ! $message->author->bot) { 
  $message->reply("```php\nif (\$message->content = ".$prefix."\"".$matches[1]."\" && ! \$message->author->bot) {\n//code to run\n};```");
      } 
      
}
    });

});

$discord->run();
?>
