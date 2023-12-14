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
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;
use Spipu\ProcessBundle\Step\StepReportInterface;
use Spipu\ProcessBundle\Step\StepReportTrait;

class GenerateError implements StepInterface, StepReportInterface
{
    use StepReportTrait;

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): bool
    {
        $logger->debug('Test of message');
        $this->addReportMessage('Test of message');

        $logger->warning('Test of warning');
        $this->addReportWarning('Test of warning');

        $logger->error('Test of error');
        $this->addReportError('Test of error');

        throw new StepException('Test of exception');
    }
}
