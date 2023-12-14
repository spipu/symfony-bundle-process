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

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class GetLocalFile implements StepInterface
{
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        $folder = $parameters->get('folder');
        $filePattern = $parameters->get('file_pattern');

        if (substr($folder, -1) !== DIRECTORY_SEPARATOR) {
            $folder .= DIRECTORY_SEPARATOR;
        }

        $logger->debug(sprintf('Search for [%s] in [%s]', $filePattern, $folder));

        $files = $this->getFiles($folder, $filePattern);
        if (count($files) > 1) {
            throw new StepException('More than 1 file is available for this pattern: ' . implode(', ', $files));
        }
        if (count($files) === 0) {
            throw new StepException('No file found for this pattern');
        }
        $file = $files[0];
        $logger->debug(sprintf('File found: %s', $file));

        return $folder . $file;
    }

    private function getFiles(string $folder, string $filePattern): array
    {
        if (!is_dir($folder)) {
            throw new StepException('The folder does not exist');
        }

        $fileList = array_diff(scandir($folder), ['.', '..']);

        $list = [];
        foreach ($fileList as $file) {
            if (!is_file($folder . '/' . $file)) {
                continue;
            }

            if (!preg_match('/^' . $filePattern . '$/', $file)) {
                continue;
            }

            $list[] = $file;
        }

        return $list;
    }
}
