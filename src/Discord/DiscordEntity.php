<?php

namespace Discord;

class DiscordEntity
{
	/**
	 * Magic function to get variables
	 */
	public function __call($name, $args)
	{
		if (preg_match("/^set([A-Z][a-zA-Z0-9]+)$/", $name, $match)) {
			$option = $this->lowercaseFirst($match[1]);
			return $this->setOption($option, $args[0]);
		} elseif (preg_match("/^get([A-Z][a-zA-Z0-9]+)$/", $name, $match)) {
			$option = $this->lowercaseFirst($match[1]);
			return $this->getOption($option);
		} else {
			return null;
		}
	}
}