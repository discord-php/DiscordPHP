<?php

namespace Discord\Builders;

use Discord\Builders\Commands\Option;
//use Discord\Parts\Interactions\Command\Option;
use InvalidArgumentException;
use JsonSerializable;

use function Discord\poly_strlen;
/**
 * Helper class used to build application commands.
 *
 * @author Mark `PeanutNL` Versluis
 */
class CommandBuilder implements JsonSerializable
{
    public const TYPE_CHAT_INPUT = 1;
    public const TYPE_USER = 2;
    public const TYPE_MESSAGE = 3;
    
    /**
     * Name of the command.
     *
     * @var string
     */
    protected string $name;

    /**
     * Description of the command. should be emtpy if the type is not CHAT_INPUT
     *
     * @var string
     */
    protected string $description = '';

    /**
     * Type of the command. The type defaults to 1
     *
     * @var int
     */
    protected int $type = self::TYPE_CHAT_INPUT;

    /**
     * The default permission of the command. If true the command is enabled when the app is added to the guild
     *
     * @var bool
     */
    protected bool $default_permission = true;

    /**
     * array with options.
     *
     * @var Option[]
     */
    protected array $options = [];

    /**
     * Creates a new command builder.
     *
     * @return $this
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * Sets the type of the command.
     *
     * @param int $type Type of the command
     *
     * @return $this
     */
    public function setType(int $type): self
    {
        if ($type < 1 || $type > 3) {
            throw new InvalidArgumentException('Invalid type provided.');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Sets the description of the command.
     *
     * @param string $description Type of the command
     *
     * @return $this
     */
    public function setDescription(string $description): self
    {
        if ($description && poly_strlen($description) > 100) {
            throw new InvalidArgumentException('Description must be less than or equal to 100 characters.');
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Sets the description of the command.
     *
     * @param string $description Type of the command
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        if ($name && poly_strlen($name) > 32) {
            throw new InvalidArgumentException('Name must be less than or equal to 32 characters.');
        }
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the default permission of the command.
     *
     * @param bool $permission Type of the command
     *
     * @return $this
     */
    public function setDefaultPermission(bool $permission): self
    {
        $this->default_permission = $permission;

        return $this;
    }

    /**
     * Adds an option to the command.
     *
     * @param Option $option The option
     *
     * @return $this
     */
    public function addOption(Option $option)
    {
        //var_dump("count: ".count($this->options));
        if (count($this->options) > 25)
        {
            throw new InvalidArgumentException('You can only have a maximum of 25 options.'); 
        }
        $this->options[] = $option;
        
        return $this;
    }

    /**
     * Removes an option from the command.
     *
     * @param Option $option Option to remove.
     *
     * @return $this
     */
    public function removeOption(Option $option)
    {
        if (($idx = array_search($option, $this->option)) !== null) {
            array_splice($this->options, $idx, 1);
        }

        return $this;
    }

    /**
     * Returns all the options in the command.
     *
     * @return Option[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Returns an array with all the options.
     *
     * @return array
     */
    public function toArray(): array
    {
        if ($this->type != 1 && strlen($this->description)) {
            throw new InvalidArgumentException('Only a command with type CHAT_INPUT accepts a description.');
        }
        
        $arrCommand = [
            "name" => $this->name,
            "description" => $this->description,
            "type" => $this->type,
            "default_permission" => $this->default_permission,
            "options" => []//$this->options
        ];
        
        foreach($this->options AS $option)
        {
            $arrCommand["options"][] = $option->toArray();
        }

        return $arrCommand;
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