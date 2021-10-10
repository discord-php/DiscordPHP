<?php

namespace Discord\Builders\Commands;

use JsonSerializable;

class Choice implements JsonSerializable
{
    /**
     * Name of the choice.
     *
     * @var string
     */
    protected string $name;

    /**
     * Value of the choice.
     *
     * @var string|int|float
     */
    protected string|int|float $value;

    /**
     * Creates a new option choice.
     *
     * @return $this
     */
    public static function new(): self
    {
        return new static();
    }

    /**
     * Sets the name of the choice.
     *
     * @param string $name name of the choice
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the value of the choice.
     *
     * @param string|int|float $value value of the choice
     *
     * @return $this
     */
    public function setValue(string|int|float $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Sets the name and value of the choice.
     *
     * @param string $name name of the choice
     * @param string $value value of the choice
     *
     * @return $this
     */
    public function setChoice(String $name, string|int|float $value)
    {
        $this->name = $name;
        $this->value = $value;

        return $this;
    }

    /**
     * Returns an array with the choice.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array("name" => $this->name, "value" => $this->value);
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