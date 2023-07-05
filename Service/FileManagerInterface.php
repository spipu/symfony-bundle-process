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

namespace Spipu\ProcessBundle\Service;

use Spipu\ProcessBundle\Entity\Process\Input;
use Spipu\ProcessBundle\Entity\Process\Process;
use Symfony\Component\HttpFoundation\File\File;

interface FileManagerInterface
{
    public function saveInputFile(Process $process, Input $input, File $file): string;

    public function getInputFilePath(Process $process, Input $input, string $filename): string;

    public function prepareLocalOutputFile(string $folderCode, string $fileCode, string $fileExtension): string;

    public function getLocalOutputFilePath(string $folderCode, string $localFileName): string;

    public function finalizeOutputFile(
        string $folderCode,
        string $fileCode,
        string $fileExtension,
        string $localFileName
    ): string;

    public function getOutputFileDownloadPath(string $folderCode, string $filename): string;
}
