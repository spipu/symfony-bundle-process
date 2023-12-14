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

namespace Spipu\ProcessBundle\Step\Test;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;
use Spipu\ProcessBundle\Step\StepReportInterface;
use Spipu\ProcessBundle\Step\StepReportTrait;

class HelloWorld implements StepInterface, StepReportInterface
{
    use StepReportTrait;

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        $message = sprintf(
            'Hello World %s from %s',
            (string) $parameters->get('name_to'),
            (string) $parameters->get('name_from')
        );

        $logger->debug($message);
        $this->addReportMessage($message);

        return $message;
    }
}
