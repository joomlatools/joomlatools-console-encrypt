<?php
/**
 * Joomlatools Console encrypt plugin - https://github.com/joomlatools/joomlatools-console-encrypt
 *
 * @copyright	Copyright (C) 2011 - 2016 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		Mozilla Public License, version 2.0
 * @link		http://github.com/joomlatools/joomlatools-console-encrypt for the canonical source repository
 */

namespace Encrypt\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \SplFileInfo;

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

		if (!extension_loaded('mcrypt')) {
			throw new \RuntimeException('This script requires mcrypt');
		}

		$path = $input->getArgument('path');

		if (empty($path)) {
			throw new \RuntimeException('Please provide a valid path');
		}

        if (!is_dir($path)) {
			throw new \RuntimeException(sprintf('The path %s does not exist', $path));
        }

        $key = $input->getOption('key');

        if (empty($key)) {
			throw new \RuntimeException('Encryption Key is required');
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

	protected function _askDeleteConfirmation($path)
	{
		$helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you want to remove the backup "'.$path.'"? [y/n]: ', false);

        if ($helper->ask($this->input, $this->output, $question)) {
			$this->_removeBackup($path);
        }
	}

	protected function _removeBackup($path)
	{
		if (is_dir($path))
		{
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
			foreach ($iterator as $f)
			{
				if ($f->isDir()) {
					rmdir($f->getRealPath());
				} else {
					unlink($f->getRealPath());
				}
			}

			rmdir($path);
		}
	}

	protected function _ignore(SplFileInfo $file)
	{
		$ignore = false;

		// Ignore files having .config extension eg. web.config
		if (!$file->isDir() && $file->getExtension() == 'config') {
			$ignore = true;
		}

		// Ignore hidden files and folders
		if ($file->getBaseName()[0] === '.') {
			$ignore = true;
		}

		return $ignore;
	}
}