<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\String;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class ToUpperString implements StepInterface
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return string
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        $value = $parameters->get('value');

        $logger->debug(sprintf('Value : %s', $value));

        return mb_convert_case($value, MB_CASE_UPPER);
    }
}
