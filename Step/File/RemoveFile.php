<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Step\File;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

/**
 * Class RemoveFile
 *
 * @package Spipu\ProcessBundle\Step\Generic
 */
class RemoveFile implements StepInterface
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return true
     * @throws \Exception
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
    {
        $file  = $parameters->get('file');

        $logger->debug(sprintf('Remove file [%s]', $file));

        if (!is_file($file)) {
            throw new StepException('This is not a file');
        }

        unlink($file);

        return true;
    }
}
