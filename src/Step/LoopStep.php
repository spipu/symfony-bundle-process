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

namespace Spipu\ProcessBundle\Step;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Service\ProcessBuilder;

class LoopStep implements StepInterface
{
    private ProcessBuilder $processBuilder;

    public function __construct(
        ProcessBuilder $processBuilder
    ) {
        $this->processBuilder = $processBuilder;
    }

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): mixed
    {
        $iterable = $parameters->get('iterable');
        if (!is_iterable($iterable) || !is_countable($iterable)) {
            throw new StepException('The iterable parameter must be an array or a countable iterable');
        }

        $stepDefinitions = $parameters->getRaw('steps');
        if (!is_array($stepDefinitions) || empty($stepDefinitions)) {
            throw new StepException('The steps parameter must be an array with at least one step');
        }

        $steps = $this->processBuilder->buildSteps(['steps' => $stepDefinitions]);

        $result = null;
        $progressCount = count($iterable);
        $progressStep = 0;
        $logger->setProgress(0);
        $logger->info(sprintf('Iterate on %d elements', count($iterable)));
        foreach ($iterable as $loopKey => $loopValue) {
            $loopKey = (string) $loopKey;
            $logger->info(sprintf('↺ Iteration [%s] - Begin', $loopKey));

            $parameters->set('loop.key', $loopKey);
            $parameters->set('loop.value', $loopValue);
            foreach ($steps as $step) {
                $step->getParameters()->setParentParameters($parameters);
                $logger->info(sprintf('↺ Iteration [%s] - Step [%s]', $loopKey, $step->getCode()));
                $result = $step->getProcessor()->execute($step->getParameters(), $logger);
                $parameters->set('loop.result.' . $step->getCode(), $result);
            }
            $parameters->set('loop.result', $result);
            $progressStep++;

            $logger->info(sprintf('↺ Iteration [%s] - End', $loopKey));
            $logger->setProgress((int) (100 * $progressStep / $progressCount));
        }

        return $result;
    }
}
