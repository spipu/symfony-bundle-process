<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Step;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;

interface StepInterface
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return mixed
     * @throws StepException
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger);
}
