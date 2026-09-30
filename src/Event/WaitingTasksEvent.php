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

namespace Spipu\ProcessBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class WaitingTasksEvent extends Event
{
    private int $nbUnscheduledTasks;
    private int $nbScheduledTasks;
    private int $waitingDelay;
    private bool $canExecute;

    public function __construct(
        int $nbUnscheduledTasks,
        int $nbScheduledTasks,
        int $waitingDelay,
        bool $canExecute
    ) {
        $this->nbUnscheduledTasks = $nbUnscheduledTasks;
        $this->nbScheduledTasks = $nbScheduledTasks;
        $this->waitingDelay = $waitingDelay;
        $this->canExecute = $canExecute;
    }

    public function getEventCode(): string
    {
        return 'spipu.process.task.waiting';
    }

    /**
     * Number of created tasks without schedule date, created before the waiting delay
     */
    public function getNbUnscheduledTasks(): int
    {
        return $this->nbUnscheduledTasks;
    }

    /**
     * Number of created tasks with a schedule date exceeded by the waiting delay
     */
    public function getNbScheduledTasks(): int
    {
        return $this->nbScheduledTasks;
    }

    public function getNbTasks(): int
    {
        return $this->nbUnscheduledTasks + $this->nbScheduledTasks;
    }

    /**
     * Waiting delay used for the detection, in minutes
     */
    public function getWaitingDelay(): int
    {
        return $this->waitingDelay;
    }

    /**
     * False if the task execution is disabled in the module configuration
     */
    public function hasTaskCanExecute(): bool
    {
        return $this->canExecute;
    }
}
