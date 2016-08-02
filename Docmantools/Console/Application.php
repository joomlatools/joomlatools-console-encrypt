<?php

namespace Docmantools\Console;

class Application extends \Symfony\Component\Console\Application
{

	protected function getDefaultCommands()
	{
		$commands = parent::getDefaultCommands();

		$commands = array_merge($commands, array(
			new Command\Encrypt(),
			new Command\Decrypt(),
		));

		return $commands;
	}
}