<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\File;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

/**
 * Class RemoveFolder
 *
 * @package Spipu\ProcessBundle\Step\Generic
 */
class RemoveFolder implements StepInterface
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return true
     * @throws \Exception
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
    {
        $folder  = $parameters->get('folder');

        $logger->debug(sprintf('Remove folder [%s]', $folder));

        if (!is_dir($folder)) {
            throw new StepException('This is not a folder');
        }

        return $this->removeDir($folder);
    }

    /**
     * @param string $dir
     * @return bool
     */
    private function removeDir(string $dir): bool
    {
        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $item = $dir.'/'.$item;

            is_dir($item) ? $this->removeDir($item) : unlink($item);
        }

        return rmdir($dir);
    }
}
