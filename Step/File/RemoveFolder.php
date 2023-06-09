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

class RemoveFolder implements StepInterface
{
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): bool
    {
        $folder  = $parameters->get('folder');

        $logger->debug(sprintf('Remove folder [%s]', $folder));

        if (!is_dir($folder)) {
            throw new StepException('This is not a folder');
        }

        return $this->removeDir($folder);
    }

    private function removeDir(string $dir): bool
    {
        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $item = $dir . '/' . $item;

            is_dir($item) ? $this->removeDir($item) : unlink($item);
        }

        return rmdir($dir);
    }
}
