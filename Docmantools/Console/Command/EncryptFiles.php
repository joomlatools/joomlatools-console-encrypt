<?php

namespace Docmantools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;

class EncryptFiles extends Command
{

	protected function configure()
	{
		parent::configure();

		$this
			->setName('encrypt:files')
			->setDescription('Encrypt Files')
			->addArgument(
				'path',
				InputArgument::REQUIRED,
				'Path to be encrypted'
			)
			->addOption(
				'key',
				null,
				InputOption::VALUE_REQUIRED,
				'Encryption key',
				'testkey'
			)
			;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		if (!extension_loaded('mcrypt'))
		{
			$output->writeln('This script requires mcrypt.');
			return;
		}

		$path = $input->getArgument('path');
        if (empty($path) || !is_dir($path))
        {
        	$output->writeln('Make sure path is valid.');
        	return;
        }

        if(($key = getenv('DOCMAN_ENCRYPTION_KEY')) === false) {
            $key = $input->getOption('key');
        }

        if (empty($key))
        {
        	$output->writeln('Encryption Key is required.');
        	return;
        }

        $this->_backup($path);

		$output->writeln('Encrypting files..');
	}

	protected function _backup($dir)
	{
		$backup = dirname($dir).'/.docman-backup';

		if (!is_dir($backup)) {
			mkdir($backup, 0755, true);
		}

		if (is_dir($backup))
		{
			$result = true;
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($iterator as $f)
			{
				if ($f->isDir()) {
					$path = $backup.'/'.$iterator->getSubPathName();
					if (!is_dir($path)) {
						mkdir($path);
					}
				} else {
					copy($f, $backup.'/'.$iterator->getSubPathName());
				}
			}
		}
	}
}