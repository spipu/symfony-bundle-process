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

class PrepareFilename implements StepInterface
{
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        $folder    = $parameters->get('folder');
        $code      = $parameters->get('code');
        $extension = $parameters->get('extension');

        if (substr($folder, -1) !== DIRECTORY_SEPARATOR) {
            $folder .= DIRECTORY_SEPARATOR;
        }

        if (!is_dir($folder)) {
            if (!mkdir($folder, 0775, true)) {
                throw new StepException(sprintf('Unable to create the asked folder [%s]', $folder));
            }
        }

        $filename = $code . '.' . date('YmdHis') . '.' . uniqid() . '.' . $extension;

        $logger->debug(sprintf('Filename: [%s]', $filename));

        return $folder . $filename;
    }
}
