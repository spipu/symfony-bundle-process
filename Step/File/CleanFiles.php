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

class CleanFiles implements StepInterface
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return bool
     * @throws StepException
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): bool
    {
        $folder = (string) $parameters->get('folder');
        if (substr($folder, -1) !== DIRECTORY_SEPARATOR) {
            $folder .= DIRECTORY_SEPARATOR;
        }
        if (!is_dir($folder)) {
            throw new StepException('The asked folder does not exist');
        }

        $keepNumber = (int) $parameters->get('keep_number');
        if ($keepNumber < 1) {
            $keepNumber = 1;
        }

        $logger->debug(sprintf('Folder: [%s]', $folder));
        $logger->debug(sprintf('Keep Files: [%d]', $keepNumber));

        $files = array_diff(scandir($folder), ['.', '..']);
        sort($files);

        if (count($files) > $keepNumber) {
            $filesToDelete = array_slice($files, 0, count($files) - $keepNumber);
            foreach ($filesToDelete as $fileToDelete) {
                $logger->warning(sprintf(' => Delete [%s]', $fileToDelete));
                unlink($folder . $fileToDelete);
            }
        }

        return true;
    }
}
