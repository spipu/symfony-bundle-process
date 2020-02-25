<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Service;

use DateInterval;
use DateTime;
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
     * CronManager constructor.
     * @param TaskRepository $processTaskRepository
     * @param LogRepository $processLogRepository
     * @param Manager $processManager
     * @param Status $processStatus
     * @param ModuleConfiguration $processConfiguration
     */
    public function __construct(
        TaskRepository $processTaskRepository,
        LogRepository $processLogRepository,
        Manager $processManager,
        Status $processStatus,
        ModuleConfiguration $processConfiguration
    ) {
        $this->processTaskRepository = $processTaskRepository;
        $this->processLogRepository = $processLogRepository;
        $this->processManager = $processManager;
        $this->processStatus = $processStatus;
        $this->processConfiguration = $processConfiguration;
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

        $scheduledTaskIds = $this->processTaskRepository->getScheduledIdsToRun();

        $rerunTaskIds = $this->processTaskRepository->getIdsToRerunAutomatically(
            $this->processConfiguration->getFailedMaxRetry()
        );

        $taskIds = array_unique(array_merge($scheduledTaskIds, $rerunTaskIds));
        if (count($taskIds) == 0) {
            $output->writeln('  => No task found');
            return false;
        }

        $output->writeln(sprintf('  => %d task(s) found', count($taskIds)));

        // We are using ids because a task can take some time to execute, we must reload it just before the execution.
        foreach ($taskIds as $taskId) {
            $this->rerunWaitingTask($output, $taskId);
        }

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param int $taskId
     * @return bool
     */
    private function rerunWaitingTask(OutputInterface $output, int $taskId): bool
    {
        $task = $this->processTaskRepository->find($taskId);

        $isScheduledTask = ($task->getScheduledAt() && $task->getStatus() === $this->processStatus->getCreatedStatus());

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
            if ($task->getScheduledAt() > (new DateTime())) {
                return false;
            }
        }

        try {
            $process = $this->processManager->loadFromTask($task);
            $this->processManager->execute($process);
        } catch (\Exception $e) {
            // We do not need to log anything, all is already done in the process manager.
            $output->writeln('  <error>Error</error> - Task #'.$task->getId().' - '.$e->getMessage());
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

        $output->writeln('  => Deleted Logs: '.$nbCleaned.'');

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

        $output->writeln('  => Deleted Tasks: '.$nbCleaned.'');

        return true;
    }

    /**
     * @param int $nbdays
     * @return \DateTimeInterface
     */
    private function prepareLimitDate(int $nbdays): \DateTimeInterface
    {
        $date = new DateTime();

        $interval = 'PT1H';
        if ($nbdays > 0) {
            $interval = 'P'.$nbdays.'DT1H';
        }

        $date->sub(new DateInterval($interval));

        return $date;
    }
}
