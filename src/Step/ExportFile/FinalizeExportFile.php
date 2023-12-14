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
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\FileExportManager;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class FinalizeExportFile implements StepInterface
{
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): bool
    {
        $fileExport = $parameters->get('file_export');
        if (!($fileExport instanceof FileExportManager)) {
            throw new StepException('The parameter [file_export] must be the result of the step [PrepareExportFile]');
        }

        $fileExport->finalizeFile();

        $logger->debug(' => Final file: ' . $fileExport->getFinalFileName());

        return true;
    }
}
