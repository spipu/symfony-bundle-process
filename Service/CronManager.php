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

use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Repository\LogRepository;
use Spipu\ProcessBundle\Repository\TaskRepository;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CronManager
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 */
class CronManager
{
    /**
     * @var TaskRepository
     */
    private $processTaskRepository;

    /**
     * @var LogRepository
     */
    private $processLogRepository;

    /**
     * @var Manager
     */
    private $processManager;

    /**
     * @var Status
     */
    private $processStatus;

    /**
     * @var ModuleConfiguration
     */
    private $processConfiguration;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TaskManager
     */
    private $taskManager;

    /**
     * @param TaskRepository $processTaskRepository
     * @param LogRepository $processLogRepository
     * @param Manager $processManager
     * @param Status $processStatus
     * @param ModuleConfiguration $processConfiguration
     * @param EntityManagerInterface $entityManager
     * @param Logger $logger
     * @param TaskManager $taskManager
     */
    public function __construct(
        TaskRepository $processTaskRepository,
        LogRepository $processLogRepository,
        Manager $processManager,
        Status $processStatus,
        ModuleConfiguration $processConfiguration,
        EntityManagerInterface $entityManager,
        Logger $logger,
        TaskManager $taskManager
    ) {
        $this->processTaskRepository = $processTaskRepository;
        $this->processLogRepository = $processLogRepository;
        $this->processManager = $processManager;
        $this->processStatus = $processStatus;
        $this->processConfiguration = $processConfiguration;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->taskManager = $taskManager;
    }

    /**
     * Rerun the waiting tasks
     * @param OutputInterface $output
     * @return bool
     */
    public function rerunWaitingTasks(OutputInterface $output): bool
    {
        $output->writeln('Search tasks to execute automatically');

        if (!$this->processConfiguration->hasTaskAutomaticRerun()) {
            $output->writeln('  => disabled in app configuration');
            return false;
        }

        $waitingDate = $this->getLimitWaitingTaskDate();

        $scheduledTaskIds = $this->processTaskRepository->getScheduledIdsToRun();
        $waitingTaskIds = $this->processTaskRepository->getWaitingIdsToRun($waitingDate);

        $rerunTaskIds = $this->processTaskRepository->getIdsToRerunAutomatically(
            $this->processConfiguration->getFailedMaxRetry()
        );

        $taskIds = array_unique(array_merge($scheduledTaskIds, $waitingTaskIds, $rerunTaskIds));
        if (count($taskIds) == 0) {
            $output->writeln('  => No task found');
            return false;
        }

        $output->writeln(sprintf('  => %d task(s) found', count($taskIds)));

        sort($taskIds);
        $limitPerRun = 1000;
        if (count($taskIds) > $limitPerRun) {
            $output->writeln(sprintf('  => Limit to %d tasks', $limitPerRun));
            $taskIds = array_splice($taskIds, 0, $limitPerRun);
        }

        // We are using ids because a task can take some time to execute, we must reload it just before the execution.
        foreach ($taskIds as $taskId) {
            $this->rerunWaitingTask($output, $taskId);
        }

        return true;
    }

    /**
     * @return DateTime
     */
    private function getLimitWaitingTaskDate(): DateTime
    {
        $date = new DateTime();
        $date->sub(new DateInterval('PT15M'));

        return $date;
    }

    /**
     * @param OutputInterface $output
     * @param int $taskId
     * @return bool
     */
    private function rerunWaitingTask(OutputInterface $output, int $taskId): bool
    {
        $task = $this->processTaskRepository->find($taskId);
        $this->entityManager->refresh($task);

        $isScheduledTask = $this->isScheduledTask($task);

        if (!$isScheduledTask) {
            if (!$task->getCanBeRerunAutomatically()) {
                return false;
            }

            if ($task->getTryNumber() >= $this->processConfiguration->getFailedMaxRetry()) {
                return false;
            }

            if (!$this->processStatus->canRerun($task->getStatus())) {
                return false;
            }
        }

        if ($isScheduledTask) {
            if ($task->getScheduledAt() && $task->getScheduledAt() > (new DateTime())) {
                return false;
            }
        }

        try {
            $process = $this->processManager->loadFromTask($task);
            $this->processManager->execute($process);
        } catch (Exception $e) {
            // We do not need to log anything, all is already done in the process manager.
            $output->writeln('  <error>Error</error> - Task #' . $task->getId() . ' - ' . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @param OutputInterface $output
     * @return bool
     */
    public function cleanFinishedLogs(OutputInterface $output): bool
    {
        $output->writeln('Search finished logs to clean');

        if (!$this->processConfiguration->hasCleanupFinishedLogs()) {
            $output->writeln('  => disabled in app configuration');
            return false;
        }

        $limitDate = $this->prepareLimitDate($this->processConfiguration->getCleanupFinishedLogsAfter());
        $nbCleaned = $this->processLogRepository->deleteFinishedLogs($limitDate);

        $output->writeln('  => Deleted Logs: ' . $nbCleaned);

        return true;
    }

    /**
     * @param OutputInterface $output
     * @return bool
     */
    public function cleanFinishedTasks(OutputInterface $output): bool
    {
        $output->writeln('Search finished tasks to clean');

        if (!$this->processConfiguration->hasCleanupFinishedTasks()) {
            $output->writeln('  => disabled in app configuration');
            return false;
        }

        $limitDate = $this->prepareLimitDate($this->processConfiguration->getCleanupFinishedTasksAfter());
        $nbCleaned = $this->processTaskRepository->deleteFinishedTasks($limitDate);

        $output->writeln('  => Deleted Tasks: ' . $nbCleaned);

        return true;
    }

    /**
     * @param int $nbDays
     * @return DateTimeInterface
     */
    private function prepareLimitDate(int $nbDays): DateTimeInterface
    {
        $date = new DateTime();

        $interval = 'PT1H';
        if ($nbDays > 0) {
            $interval = 'P' . $nbDays . 'DT1H';
        }

        $date->sub(new DateInterval($interval));

        return $date;
    }

    /**
     * Check the PID of the running tasks
     * @param OutputInterface $output
     * @return bool
     */
    public function checkRunningTasksPid(OutputInterface $output): bool
    {
        $output->writeln('Search running tasks');

        $taskIds = $this->processTaskRepository->getRunningIdsToCheck(5);
        if (count($taskIds) == 0) {
            $output->writeln('  => No task found');
            return false;
        }

        $output->writeln(sprintf('  => %d task(s) found', count($taskIds)));

        // We are using ids because we must reload it just before the check, to be sure of its status.
        foreach ($taskIds as $taskId) {
            $this->checkRunningTaskPid($output, $taskId);
        }

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param int $taskId
     * @return void
     */
    private function checkRunningTaskPid(OutputInterface $output, int $taskId): void
    {
        $task = $this->processTaskRepository->find($taskId);

        $output->writeln('   - Task #' . $task->getId());

        if ($task->getStatus() !== $this->processStatus->getRunningStatus()) {
            $output->writeln('     => <comment>Wrong Status</comment>');
            return;
        }

        if ($task->getPidValue() === null || $task->getPidValue() < 1) {
            $output->writeln('     => <comment>No PID</comment>');
            return;
        }

        if (!$this->taskManager->isPidRunning($task)) {
            $errorMessage = 'Process not found';
            $output->writeln("     => <error>$errorMessage</error>");

            $log = $task->getLogs()->last();
            if ($log) {
                $logger = clone $this->logger;
                $logger->initFromExistingLog($log);
                $logger->critical($errorMessage);
                $logger->finish(Status::FAILED);
            }
            $task->incrementTry($errorMessage, false);
            $task->setStatus(Status::FAILED);
            $this->entityManager->flush();

            return;
        }

        $output->writeln('     => <info>Process found</info>');
        $task->setPidLastSeen(new DateTime());
        $this->entityManager->flush();
    }

    /**
     * @param Task $task
     * @return bool
     */
    private function isScheduledTask(Task $task): bool
    {
        $waitingDate = $this->getLimitWaitingTaskDate();

        return (
            ($task->getStatus() === $this->processStatus->getCreatedStatus())
            && ($task->getScheduledAt() || ($task->getCreatedAt() < $waitingDate))
        );
    }
}
