<?php

namespace Discord\Builders\Commands;

use Discord\Builders\Commands\Choice;
use InvalidArgumentException;
use JsonSerializable;

class Option implements JsonSerializable
{
    public const TYPE_SUB_COMMAND = 1;
    public const TYPE_SUB_COMMAND_GROUP = 2;
    public const TYPE_STRING = 3;
    public const TYPE_INTEGER = 4;
    public const TYPE_BOOLEAN = 5;
    public const TYPE_USER = 6;
    public const TYPE_CHANNEL = 7;
    public const TYPE_ROLE = 8;
    public const TYPE_MENTIONABLE = 9;
    public const TYPE_NUMBER = 10;

    /**
     * Name of the option.
     *
     * @var string
     */
    protected string $name;

    /**
     * Description of the option.
     *
     * @var string
     */
    protected string $description;

    /**
     * Type of the option
     *
     * @var int
     */
    protected int $type;

    /**
     * requirement of the option
     *
     * @var bool
     */
    protected bool $required;

    /**
     * channel types of the option
     *
     * @var int
     */
    protected int $channel_types;

    /**
     * array with options.
     *
     * @var Option[]
     */
    protected $options = [];

    /**
     * array with choices.
     *
     * @var Choice[]
     */
    protected $choices = [];
    
    /**
     * Creates a new command option.
     *
     * @return $this
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * Sets the name of the option.
     *
     * @param string $name name of the option
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the description of the option.
     *
     * @param string $description description of the option
     *
     * @return $this
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Sets the requirement of the option.
     *
     * @param bool $require requirement of the option
     *
     * @return $this
     */
    public function setRequire(bool $require)
    {
        $this->required = $require;

        return $this;
    }

    /**
     * Sets the type of the option.
     *
     * @param string $type type of the option
     *
     * @return $this
     */
    public function setType(string $type)
    {
        if ($type < 1 || $type > 10)
        {
            throw new InvalidArgumentException('Invalid type provided.');
        }
        $this->type = $type;

        return $this;
    }

    /**
     * Sets the type of the option.
     *
     * @param string $type type of the option
     *
     * @return $this
     */
    public function setChannelType(int $type = 0)
    {
        if ($type < 0 || $type > 13)
        {
            throw new InvalidArgumentException('Invalid channel type provided.');
        }
        $this->channel_type = $type;

        return $this;
    }

    /**
     * Sets the default values of the option.
     *
     * @param string $name name of the option
     * @param string $description description of the option
     * @param int    $type type of the option
     * @param bool   $require requirement of the option
     * @param int    $channeltype channel type of the option
     *
     * @return $this
     */
    public function setOption(string $name, string $description, int $type, bool $require = false, int $channeltype = 0)
    {
        if ($type < 1 || $type > 10)
        {
            throw new InvalidArgumentException('Invalid type provided.');
        }
        if ($channeltype < 0 || $channeltype > 13)
        {
            throw new InvalidArgumentException('Invalid channel type provided.');
        }

        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->required = $require;
        $this->channel_types = $channeltype;

        return $this;
    }
    
    /**
     * Adds an option to the option.
     *
     * @param Option $option The option
     *
     * @return $this
     */
    public function addOption(Option $option)
    {
        //https://discord.com/developers/docs/interactions/application-commands#application-command-object-application-command-option-structure
        // Does not say it has a max...
        $this->options[] = $option;

        return $this;
    }
    
    /**
     * Adds a choice to the option.
     *
     * @param Choice $choice The choice
     *
     * @return $this
     */
    public function addChoice(Choice $choice)
    {
        if (count($this->choices) > 25)
        {
            throw new InvalidArgumentException('You can only have a maximum of 25 Choices.'); 
        }
        
        $this->choices[] = $choice;

        return $this;
    }

    /**
     * Removes an option from the option.
     *
     * @param Option $option Option to remove.
     *
     * @return $this
     */
    public function removeOption(Option $option)
    {
        if (($idx = array_search($option, $this->options)) !== null) {
            array_splice($this->options, $idx, 1);
        }

        return $this;
    }

    /**
     * Removes an option from the command.
     *
     * @param Option $option Option to remove.
     *
     * @return $this
     */
    public function removeChoice(Choice $choice)
    {
        if (($idx = array_search($choice, $this->choices)) !== null) {
            array_splice($this->choices, $idx, 1);
        }

        return $this;
    }

    /**
     * Returns all the options in the option.
     *
     * @return Option[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Returns all the choices in the option.
     *
     * @return Choice[]
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

     /**
     * Returns an array with all the option.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (count($this->choices) > 0 && ($this->type != self::TYPE_STRING && $this->type != self::TYPE_INTEGER && $this->type != self::TYPE_NUMBER))
        {
            //echo "Komt hier hoor",PHP_EOL;
            throw new InvalidArgumentException('Choices are only available for STRING, INTEGER or NUMBER types.'); 
        }
        
        $arrOption = [
            "name" => $this->name,
            "description" => $this->description,
            "type" => $this->type,
        ];
        
        
        if (isset($this->required))
            $arrOption["required"] = $this->required;
        
        if (isset($this->channel_types))
            $arrOption["channel_types"] = $this->channel_types;
        
        $arrOption["options"] = array();
        $arrOption["choices"] = array();
        foreach($this->options AS $option)
        {
            $arrOption["options"][] = $option->toArray();
        }

        foreach($this->choices AS $choice)
        {
            $arrOption["choices"][] = $choice->toArray();
        }

        return $arrOption;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
?>