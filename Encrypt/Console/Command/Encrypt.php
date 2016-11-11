<?php

namespace Encrypt\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;

class Encrypt extends AbstractCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('encrypt')
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
		$backup = $this->_getBackupPath($path);

        // Create a backup.
        $output->writeln('Creating a Backup...');
        $this->_backup($path, $backup);

		// Encrypt Files.
		$output->writeln('Encrypting files..');
		$this->_encryptFiles($backup, $path);

		// Confirm the deletion of the backup
		$this->_askDeleteConfirmation($backup);
	}

	protected function _encryptFiles($source, $target)
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
					$this->_encryptFile($f, $target.'/'.$iterator->getSubPathName());
				}
			}
		}
	}

	protected function _encryptFile($file, $target)
	{
		$result = false;

		if(($handle = fopen($file, 'r')) === 0) {
			throw new \RuntimeException(sprintf('Unable to read file at: %s', $file));
        }

        $destination = $this->_createEncryptedStream($target);

        // Copy original file into encrypted location file
        while(!feof($handle)) {
        	$result = (fwrite($destination, fread($handle, 8192)) !== false);
        }

        // Cleanup
        @fclose($destination);
        @fclose($handle);

        return $result;
	}

    protected function _createEncryptedStream($target)
    {
        if(($stream = fopen($target, 'w+')) === 0) {
			throw new \RuntimeException(sprintf('Unable to create file at: %s', $target));
        }

        // Generate the IV
        $iv = $this->_createIV();
        fwrite($stream, $iv);

        stream_filter_append($stream, 'convert.base64-encode', STREAM_FILTER_WRITE);
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