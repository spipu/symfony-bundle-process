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

use Doctrine\ORM\EntityManagerInterface;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Exception\ProcessException;

class TaskManager
{
    private Status $status;
    private EntityManagerInterface $entityManager;

    public function __construct(
        Status $status,
        EntityManagerInterface $entityManager
    ) {
        $this->status = $status;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Task $task
     * @return bool
     * @SuppressWarnings(PMD.ErrorControlOperator)
     */
    public function isPidRunning(Task $task): bool
    {
        if ($task->getPidValue() === null || $task->getPidValue() < 1) {
            return false;
        }

        $pid = $task->getPidValue();
        $sid = @posix_getsid($pid);

        return ($sid !== false) && ((int) $sid > 0);
    }

    public function kill(Task $task, string $reason): void
    {
        if (!$this->status->canKill($task->getStatus())) {
            throw new ProcessException('spipu.process.error.kill');
        }

        if ($this->isPidRunning($task)) {
            if (!posix_kill($task->getPidValue(), 9)) {
                $errorId = posix_get_last_error();
                $errorMsg = 'Error during kill - Error #' . $errorId;
                if ($errorId > 0) {
                    $errorMsg .= ' - ' . posix_strerror($errorId);
                }
                throw new ProcessException($errorMsg);
            }
        }

        $task
            ->setStatus($this->status::FAILED)
            ->incrementTry($reason, false);

        $this->entityManager->flush();
    }
}
