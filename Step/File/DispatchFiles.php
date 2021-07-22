<?php

/**
 * This file is part of a Spipu Bundle
 *
 * (c) Laurent Minguet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\File;

use Exception;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

/**
 * Class DispatchFiles
 *
 * @package Spipu\ProcessBundle\Step\Generic
 */
class DispatchFiles implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return int
     * @throws Exception
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
    {
        $this->logger = $logger;

        $folder  = $parameters->get('folder');
        $mapping = $parameters->get('mapping');
        $keepOnlyOneFile = (bool) $parameters->get('keep_only_one_file');

        $logger->debug(sprintf('Dispatch files from [%s]', $folder));
        $logger->debug('Keep only one file: ' . ($keepOnlyOneFile ? 'Yes' : 'No'));

        $files = array_diff(scandir($folder), ['.', '..']);

        $found = 0;
        foreach ($mapping as $item) {
            if (!is_dir($item['destination'])) {
                mkdir($item['destination'], 0775, true);
            }
            foreach ($files as $key => $file) {
                if (preg_match('/^' . $item['file_pattern'] . '$/', $file)) {
                    $found++;
                    unset($files[$key]);
                    $files = array_values($files);

                    $this->dispatchFile(
                        $file,
                        $folder,
                        $item['destination'],
                        ($keepOnlyOneFile ? $item['file_pattern'] : null)
                    );

                    continue 2;
                }
            }
            throw new StepException(
                sprintf(
                    'File not found for [%s] => [%s]',
                    $item['file_pattern'],
                    $item['destination']
                )
            );
        }

        $logger->warning(sprintf('No matching files: %s', print_r($files, true)));

        return $found;
    }

    /**
     * @param string $filename
     * @param string $source
     * @param string $destination
     * @param string|null $filePattern
     * @return void
     */
    private function dispatchFile(string $filename, string $source, string $destination, ?string $filePattern): void
    {
        $this->logger->debug(sprintf(' move [%s] to [%s]', $filename, $destination));

        if (is_file($destination . DIRECTORY_SEPARATOR . $filename)) {
            $this->logger->warning('  => The file already exists and will be replaced');
            unlink($destination . DIRECTORY_SEPARATOR . $filename);
        }

        if ($filePattern !== null) {
            $otherFiles = array_diff(scandir($destination), ['.', '..']);
            foreach ($otherFiles as $otherFile) {
                if (preg_match('/^' . $filePattern . '$/', $otherFile)) {
                    $this->logger->warning(sprintf('  => The old file [%s] will be deleted', $otherFile));
                    unlink($destination . DIRECTORY_SEPARATOR . $otherFile);
                }
            }
        }

        rename($source . DIRECTORY_SEPARATOR . $filename, $destination . DIRECTORY_SEPARATOR . $filename);
        chmod($destination . DIRECTORY_SEPARATOR . $filename, 0664);
    }
}
