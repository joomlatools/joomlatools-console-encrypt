<?php
/**
 * Joomlatools Console encrypt plugin - https://github.com/joomlatools/joomlatools-console-encrypt
 *
 * @copyright	Copyright (C) 2011 - 2016 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		Mozilla Public License, version 2.0
 * @link		http://github.com/joomlatools/joomlatools-console-encrypt for the canonical source repository
 */

namespace Encrypt\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;

use Encrypt\Console\AbstractCommand;

/**
 * Decrypt plugin class.
 *
 * @author  Allan Pilarca <https://github.com/allanpilarca>
 * @package Joomlatools\Console
 */
class Decrypt extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('decrypt')
			->setDescription('Decrypt Files')
			->addArgument(
				'path',
				InputArgument::REQUIRED,
				'Path to be decrypted'
			)
			->addOption(
				'key',
				null,
				InputOption::VALUE_REQUIRED,
				'Encryption key',
				'insecure'
			)
			->addOption('cipher',
				null,
				InputOption::VALUE_REQUIRED,
				'Cipher',
				MCRYPT_RIJNDAEL_128
			)
			->addOption('mode',
				null,
				InputOption::VALUE_REQUIRED,
				'Mode',
				MCRYPT_MODE_CBC
			)
			;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$path   = $this->path;
		$backup = $this->_getBackupPath($path, 'encrypted');

        // Create a backup.
        $output->writeln('Creating a Backup...');
        $this->_backup($path, $backup);

		// Encrypt Files.
		$output->writeln('Decrypting files..');
		$this->_decryptFiles($backup, $path);

		// Confirm the deletion of the backup
		$this->_askDeleteConfirmation($backup);
	}

	protected function _decryptFiles($source, $target)
	{
		if (is_dir($source) && is_dir($target))
		{
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($iterator as $f)
			{
				if ($this->_ignore($f)) {
					continue;
				}

				if ($f->isDir())
				{
					$path = $target.'/'.$iterator->getSubPathName();
					if (!is_dir($path)) {
						mkdir($path);
					}
				} else {
					$this->_decryptFile($f->getPathName(), $target.'/'.$iterator->getSubPathName());
				}
			}
		}
	}

	protected function _decryptFile($file, $target)
	{
        $stream = $this->_createDecryptedStream($file);
        $output = fopen($target, 'w+');

        while(!feof($stream)) {
        	fwrite($output, fread($stream, 8192));
        }

        fclose($output);
        fclose($stream);
	}

    protected function _createDecryptedStream($source)
    {
  		if(($stream = fopen($source, 'r')) === 0) {
			throw new \RuntimeException(sprintf('Unable to read file at: %s', $source));
        }

        // Generate the IV
        $ivsize = mcrypt_get_iv_size($this->input->getOption('cipher'), $this->input->getOption('mode'));
        $iv = fread($stream, $ivsize);
        // rewind($stream);

        stream_filter_append($stream, 'mdecrypt.'.$this->input->getOption('cipher'), STREAM_FILTER_READ, array(
            'mode' => $this->input->getOption('mode'),
            'key'  => $this->input->getOption('key'),
            'iv'   => $iv,
        ));
        stream_filter_append($stream, 'convert.base64-decode', STREAM_FILTER_READ);

        return $stream;
    }
}