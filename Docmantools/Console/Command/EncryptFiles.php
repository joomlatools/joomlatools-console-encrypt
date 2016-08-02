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
		$this->input  = $input;
		$this->output = $output;

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

		$directory    = basename($path);
		$this->backup = dirname($path).'/.'.$directory.'-backup';

        // Create a backup.
        $output->writeln('Creating a Backup...');
        $this->_backup($path);

		$cipher = $input->getOption('cipher');
		$mode   = $input->getOption('mode');

		// Encrypt Files.
		$output->writeln('Encrypting files..');
		$this->_encryptFiles($this->backup, $path);

	}

	protected function _backup($dir)
	{
		$backup = $this->backup;

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

	protected function _encryptFiles($source, $target)
	{
		if (is_dir($source) && is_dir($target))
		{
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($iterator as $f)
			{
				if ($f->isDir())
				{
					$path = $target.'/'.$iterator->getSubPathName();
					if (!is_dir($path)) {
						mkdir($path);
					}
				} else {
					$this->_encryptFile($f, $target.'/'.$iterator->getSubPathName());
				}
			}
		}
	}

	protected function _encryptFile($file, $target)
	{
		$result = false;

        if (!$file instanceof SplFileObject) {
            $file = new SplFileObject($file);
        }

        $destination = $this->_createEncryptedStream($target);

        // Copy original file into encrypted location file
        foreach ($file as $line) {
            $result = (fwrite($destination, $line) !== false);
        }

        // Cleanup
        @fclose($destination);
        // @unlink($file->getRealPath());

        return $result;
	}

    protected function _createEncryptedStream($target)
    {
        if(($stream = fopen($target, 'w+')) === 0) {
            throw new KControllerExceptionActionFailed('Unable to create file at: '.$target);
        }

        // Generate the IV
        $iv = $this->_createIV();
        fwrite($stream, $iv);

        stream_filter_append($stream, 'mcrypt.'.$this->input->getOption('cipher'), STREAM_FILTER_WRITE, array(
            'mode' => $this->input->getOption('mode'),
            'key'  => $this->input->getOption('key'),
            'iv'   => $iv,
        ));

        return $stream;
    }

    protected function _createIV()
    {
        mt_srand();

        $size = mcrypt_get_iv_size($this->input->getOption('cipher'), $this->input->getOption('mode'));
        $iv   = mcrypt_create_iv($size, MCRYPT_RAND);

        return $iv;
    }
}