<?php

namespace Docmantools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \SplFileObject;

class AbstractCommand extends Command
{
	protected $input;
	protected $output;
	protected $key;
	protected $path;

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$this->input  = $input;
		$this->output = $output;

		$this->_check();
	}

	protected function _check()
	{
		$input  = $this->input;
		$output = $this->output;

		if (!extension_loaded('mcrypt'))
		{
			$output->writeln('This script requires mcrypt.');
			exit();
		}

		$path = $input->getArgument('path');
        if (empty($path) || !is_dir($path))
        {
        	$output->writeln('Make sure path is valid.');
        	exit();
        }

        if(($key = getenv('DOCMAN_ENCRYPTION_KEY')) === false) {
            $key = $input->getOption('key');
        }

        if (empty($key))
        {
        	$output->writeln('Encryption Key is required.');
        	exit();
        }

		$this->key  = $key;
		$this->path = $path;
	}

	protected function _backup($dir, $backup_path)
	{
		if (!is_dir($backup_path)) {
			mkdir($backup_path, 0755, true);
		}

		if (is_dir($backup_path))
		{
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($iterator as $f)
			{
				if ($f->isDir()) {
					$path = $backup_path.'/'.$iterator->getSubPathName();
					if (!is_dir($path)) {
						mkdir($path);
					}
				} else {
					copy($f, $backup_path.'/'.$iterator->getSubPathName());
				}
			}
		}
	}

	protected function _getBackupPath($path, $type = 'backup')
	{
		$directory   = basename($path);
		$backup_path = dirname($path).'/.'.$directory.'-'.$type;

		return $backup_path;
	}
}