<?php
/**
 * An example that registers a slash command with an autocomplete callback.
 *
 * Type "/roll" in chat, the option "sides" will trigger the autocomplete callback, listing available options.
 *
 * Run this example bot from main directory using command:
 * php examples/command.php
 */
declare(strict_types = 1);

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\ApplicationCommand;
use Discord\Parts\Interactions\ApplicationCommandAutocomplete;
use Discord\Parts\Interactions\Command\Choice;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Command\Option;
use Discord\WebSockets\Intents;

require_once __DIR__.'/../vendor/autoload.php';

ini_set('memory_limit', -1);

// a class to handle the command callbacks
// we're going to roll dice
class DiceRollHandler
{
    public const NAME = 'roll';

    public function __construct(
        protected Discord $discord,
    ) {
        // noop
    }

    public function buildCommand():CommandBuilder
    {
        // an option "sides"
        $sides = (new Option($this->discord))
            ->setType(Option::INTEGER)
            ->setName('sides')
            ->setDescription('sides on the die')
            ->setAutoComplete(true);

        // the command "roll"
        return (new CommandBuilder)
            ->setType(Command::CHAT_INPUT)
            ->setName(static::NAME)
            ->setDescription('rolls an n-sided die')
            ->addOption($sides);
    }

    // attempt to register a global slash command
    public function register():static
    {
        // after the command was created successfully, you should disable this code
        $this->discord->application->commands->save(new Command($this->discord, $this->buildCommand()->toArray()));

        return $this;
    }

    // add listener(s) for the command and possible subcommands
    public function listen():static
    {
        $registeredCommand = $this->discord->listenCommand(DiceRollHandler::NAME, $this->execute(...), $this->autocomplete(...));

        // you may register different handlers for each subcommand here
#       foreach(['subcommand1', 'subcommand2', /*...*/] as $subcommand){
#           $registeredCommand->addSubCommand($subcommand, $this->execute(...), $this->autocomplete(...));
#       }

        return $this;
    }

    // the command callback
    public function execute(ApplicationCommand $interaction, Collection $params):void
    {
        $sides = ($interaction->data->options->offsetGet('sides')?->value ?? 20);

        // sanity check
        if (! in_array($sides, [4, 6, 8, 10, 12, 20], true)) {
            $sides = 20;
        }

        $message = sprintf('%s rolled %s with a %s-sided die', $interaction->user, random_int(1, $sides), $sides);

        // respond to the command with an interaction message
        $interaction->respondWithMessage((new MessageBuilder)->setContent($message));
    }

    // the autocomplete callback (must return array to trigger a response)
    public function autocomplete(ApplicationCommandAutocomplete $interaction):array|null
    {
        // respond if the desired option is focused
        /** @see \Discord\Parts\Interactions\Request\Option */
        if ($interaction->data->options->offsetGet('sides')->focused) {
            // the dataset, e.g. fetched from a database (25 results max)
            $dataset = [4, 6, 8, 10, 12, 20];
            $choices = [];

            foreach ($dataset as $sides) {
                $choices[] = new Choice($this->discord, ['name' => sprintf('%s-sided', $sides), 'value' => $sides]);
            }

            return $choices;
        }

        return null;
    }
}

// invoke the discord client
$dc = new Discord([
    // https://discord.com/developers/applications/<APP_ID>>/bot
    'token' => 'YOUR_DISCORD_BOT_TOKEN',
    // Note: MESSAGE_CONTENT, GUILD_MEMBERS and GUILD_PRESENCES are privileged, see https://dis.gd/mcfaq
    'intents' => (Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT),
]);

$dc->on('init', function (Discord $discord):void {
    echo "Bot is ready!\n";

    // invoke the command handler
    $commandHandler = new DiceRollHandler($discord);

    // this method shouldn't be run on each bot start
    #	if($options->registerCommands){
        $commandHandler->register();
    #	}

    // add a listener for the command
    $commandHandler->listen();
});

$dc->run();
