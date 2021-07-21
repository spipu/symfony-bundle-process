<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\Test;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class HelloWorld implements StepInterface
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return string
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        $message = sprintf(
            'Hello World %s from %s',
            (string) $parameters->get('name_to'),
            (string) $parameters->get('name_from')
        );

        $logger->debug($message);

        return $message;
    }
}
