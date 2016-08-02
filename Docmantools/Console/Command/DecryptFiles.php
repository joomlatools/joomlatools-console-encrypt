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

class DecryptFiles extends Command
{

	protected function configure()
	{
		parent::configure();

		$this
			->setName('decrypt:files')
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
		$this->backup = dirname($path).'/.'.$directory.'-encrypted';

        // Create a backup.
        $output->writeln('Creating a Backup...');
        $this->_backup($path);

		$cipher = $input->getOption('cipher');
		$mode   = $input->getOption('mode');

		// Encrypt Files.
		$output->writeln('Decrypting files..');
		$this->_decryptFiles($this->backup, $path);

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

	protected function _decryptFiles($source, $target)
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
					// $this->_decryptFile($f, $target.'/'.$iterator->getSubPathName());
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
        	fwrite($output, fread($stream, 1024));
        }

        // fwrite($output, stream_get_contents($stream));
        fclose($output);
        fclose($stream);
	}

    protected function _createDecryptedStream($source)
    {
  		if(($stream = fopen($source, 'r')) === 0) {
            throw new KControllerExceptionActionFailed('Unable to read file at: '.$source);
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