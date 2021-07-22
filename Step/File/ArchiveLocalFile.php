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

use DateTime;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class ArchiveLocalFile implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return string
     * @throws StepException
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
    {
        $this->logger = $logger;

        $folder = $parameters->get('folder');
        if (substr($folder, -1) !== DIRECTORY_SEPARATOR) {
            $folder .= DIRECTORY_SEPARATOR;
        }
        $this->logger->debug(sprintf('Archive Folder: [%s]', $folder));

        $keepNumber = $this->getKeepNumberParameter($parameters);
        $keepPattern = $this->getKeepPatternParameter($parameters);
        $this->logger->debug(sprintf('Keep Files: [%s]', ($keepNumber ? (string) $keepNumber : 'All')));
        $this->logger->debug(sprintf('Keep Pattern: [%s]', ($keepPattern ? $keepPattern : 'none')));

        $filename = $parameters->get('filename');
        if (!is_file($filename)) {
            throw new StepException(sprintf("%s does not exist.", $filename));
        }

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $time = (new DateTime())->format('YmdHisv');
        $archiveFilename = $folder.basename($filename).'.'.$time;

        $this->logger->debug(sprintf('File archived [%s]', basename($archiveFilename)));
        rename($filename, $archiveFilename);

        if ($keepNumber) {
            $this->keepFiles($folder, $keepNumber, $keepPattern);
        }

        return $archiveFilename;
    }

    /**
     * @param ParametersInterface $parameters
     * @return int|null
     */
    private function getKeepNumberParameter(ParametersInterface $parameters): ?int
    {
        $parameters->setDefaultValue('keep_number', null);

        $value = $parameters->get('keep_number');

        if ($value) {
            $value = (int) $value;
            if ($value > 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param ParametersInterface $parameters
     * @return string|null
     */
    private function getKeepPatternParameter(ParametersInterface $parameters): ?string
    {
        $parameters->setDefaultValue('keep_pattern', null);

        $value = $parameters->get('keep_pattern');

        if (is_string($value)) {
            $value = trim($value);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param string $folder
     * @param int $keepNumber
     * @param string|null $keepPattern
     * @return bool
     */
    private function keepFiles(string $folder, int $keepNumber, ?string $keepPattern): bool
    {
        $list = [];

        $files = array_diff(scandir($folder), ['.', '..']);
        foreach ($files as $file) {
            if ($keepPattern !== null && !preg_match('/^' . $keepPattern . '\.[0-9]+$/', $file)) {
                continue;
            }

            if (!preg_match('/\.([0-9]+)$/', $file, $match)) {
                continue;
            }
            $list[$file] = $match[1];
        }

        if (count($list) <= $keepNumber) {
            return false;
        }

        asort($list);
        $filesToDelete = array_keys(array_slice($list, 0, count($list) - $keepNumber));


        $this->logger->debug(sprintf('Old Files to delete: %s', print_r($filesToDelete, true)));
        foreach ($filesToDelete as $fileToDelete) {
            unlink($folder . $fileToDelete);
        }

        return true;
    }
}
