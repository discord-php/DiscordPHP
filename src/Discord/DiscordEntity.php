<?php

namespace Discord;

class DiscordEntity
{
	public function __get($property)
	{
		if(property_exists($this, $property)) return $this->{$property};
		return null;
	}
}