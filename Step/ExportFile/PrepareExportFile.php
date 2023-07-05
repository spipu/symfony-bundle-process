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

namespace Spipu\ProcessBundle\Step\ExportFile;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\FileExportManager;
use Spipu\ProcessBundle\Service\FileManagerInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class PrepareExportFile implements StepInterface
{
    private FileManagerInterface $fileManager;

    public function __construct(FileManagerInterface $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): FileExportManager
    {
        $folderCode = (string) $parameters->get('folder_code');
        $fileCode = (string) $parameters->get('file_code');
        $fileExtension = (string) $parameters->get('file_extension');

        $logger->debug(sprintf(' - folder code: [%s]', $folderCode));
        $logger->debug(sprintf(' - file code:   [%s]', $fileCode));
        $logger->debug(sprintf(' - file ext:    [%s]', $fileExtension));

        $fileExport = new FileExportManager($this->fileManager, $folderCode, $fileCode, $fileExtension);
        $fileExport->prepare();

        $logger->debug(' => Local file: ' . $fileExport->getLocalFilePath());

        return $fileExport;
    }
}
