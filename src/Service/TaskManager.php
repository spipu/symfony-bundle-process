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
    public const HOST_MAX_LENGTH = 255;

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
        if (!$this->hasPid($task) || !$this->isPidOnCurrentHost($task)) {
            return false;
        }

        $pid = $task->getPidValue();
        $sid = @posix_getsid($pid);

        return ($sid !== false) && ((int) $sid > 0);
    }

    /**
     * The host is saved in a varchar column: if it is not printable ASCII or too long, a hash is used instead
     */
    public function getCurrentHost(): ?string
    {
        $host = gethostname();
        if ($host === false) {
            return null;
        }

        $host = trim($host);
        if ($host === '') {
            return null;
        }

        if (strlen($host) > self::HOST_MAX_LENGTH || preg_match('/^[\x21-\x7E]+$/', $host) !== 1) {
            return 'md5:' . md5($host);
        }

        return $host;
    }

    /**
     * A task without host comes from a version that did not save it: it is considered on the current host
     */
    public function isPidOnCurrentHost(Task $task): bool
    {
        return $task->getPidHost() === null || $task->getPidHost() === $this->getCurrentHost();
    }

    private function hasPid(Task $task): bool
    {
        return $task->getPidValue() !== null && $task->getPidValue() > 0;
    }

    public function kill(Task $task, string $reason): void
    {
        if (!$this->status->canKill($task->getStatus())) {
            throw new ProcessException('spipu.process.error.kill');
        }

        if ($this->hasPid($task) && !$this->isPidOnCurrentHost($task)) {
            throw new ProcessException('spipu.process.error.kill_host');
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
