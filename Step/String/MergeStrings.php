<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\String;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class MergeStrings implements StepInterface
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return string
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        return implode($parameters->get('glue'), $parameters->get('strings'));
    }
}
