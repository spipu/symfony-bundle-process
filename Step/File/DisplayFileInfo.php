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
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class DisplayFileInfo implements StepInterface
{
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): bool
    {
        $filename  = (string) $parameters->get('filename');

        clearstatcache(true, $filename);

        $exist = is_file($filename);

        $logger->debug('File Information');
        $logger->debug(' - filename: ' . basename($filename));
        $logger->debug(' - directory: ' . dirname($filename));
        $logger->debug(' - exist: ' . ($exist ? 'Yes' : 'no'));

        if ($exist) {
            $logger->debug(sprintf(' - size: %s Ko', number_format(filesize($filename) / 1024, 2, '.', ' ')));
            $logger->debug(sprintf(' - date: %s', date('Y-m-d H:i:s', (int) filemtime($filename))));
        }

        return true;
    }
}
