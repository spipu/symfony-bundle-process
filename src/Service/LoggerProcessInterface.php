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

namespace Spipu\ProcessBundle\Service;

use Spipu\ProcessBundle\Entity\Task;

/**
 * @SuppressWarnings(PMD.TooManyPublicMethods)
 */
interface LoggerProcessInterface extends LoggerInterface
{
    /**
     * Init the logger for a new process
     * @param string $processCode
     * @param int $nbSteps
     * @param Task|null $task
     * @return int
     */
    public function init(string $processCode, int $nbSteps, ?Task $task): int;

    /**
     * Set the current step, form 0 to n-1
     * @param int $currentStep
     * @param bool $ignoreInProgress
     * @return void
     */
    public function setCurrentStep(int $currentStep, bool $ignoreInProgress): void;

    /**
     * Finish the logger for the current process
     * @param string $status
     * @return void
     */
    public function finish(string $status): void;

    public function setLoggerOutput(?LoggerOutputInterface $loggerOutput): void;
}
