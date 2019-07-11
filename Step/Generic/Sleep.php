<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Step\Generic;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class Sleep implements StepInterface
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return bool
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): bool
    {
        $seconds = (int) $parameters->get('seconds');

        $logger->debug('Sleep for '.$seconds.' seconds');

        sleep($seconds);

        return true;
    }
}
