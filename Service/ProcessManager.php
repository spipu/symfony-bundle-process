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

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Spipu\CoreBundle\Service\AsynchronousCommand;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Entity\Process;
use Spipu\ProcessBundle\Exception\InputException;
use Spipu\ProcessBundle\Exception\OptionException;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Exception\ProcessException;
use Throwable;

/**
 * Class Manager
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 * @SuppressWarnings(PMD.ExcessiveClassComplexity)
 */
class ProcessManager
{
    private ConfigReader $configReader;
    private MainParameters $mainParameters;
    private LoggerProcessInterface $logger;
    private EntityManagerInterface $entityManager;
    private AsynchronousCommand $asynchronousCommand;
    private InputsFactory $inputsFactory;
    private ReportManager $reportManager;
    private FileManagerInterface $fileManager;
    private ?LoggerOutputInterface $loggerOutput = null;

    public function __construct(
        ConfigReader $configReader,
        MainParameters $mainParameters,
        LoggerProcessInterface $logger,
        EntityManagerInterface $entityManager,
        AsynchronousCommand $asynchronousCommand,
        InputsFactory $inputsFactory,
        ReportManager $reportManager,
        FileManagerInterface $fileManager
    ) {
        $this->configReader = $configReader;
        $this->mainParameters = $mainParameters;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->asynchronousCommand = $asynchronousCommand;
        $this->inputsFactory = $inputsFactory;
        $this->reportManager = $reportManager;
        $this->fileManager = $fileManager;
    }

    public function getConfigReader(): ConfigReader
    {
        return $this->configReader;
    }

    public function setLoggerOutput(?LoggerOutputInterface $loggerOutput): void
    {
        $this->loggerOutput = $loggerOutput;
    }

    public function load(string $code): Process\Process
    {
        $processDefinition = $this->configReader->getProcessDefinition($code);

        $processOptions = $this->loadPrepareOptions($processDefinition['options']);
        $processInputs = $this->loadPrepareInputs($processDefinition['inputs']);

        $processParameters = $this->loadPrepareParameters($processDefinition['parameters']);
        $processParameters->setParentParameters($this->mainParameters);

        $process = new Process\Process(
            $processDefinition['code'],
            $processDefinition['name'],
            $processOptions,
            $processInputs,
            $processParameters,
            $this->loadPrepareSteps($processDefinition)
        );

        if ($process->getOptions()->canBePutInQueue()) {
            $task = $this->loadPrepareTask($process->getCode());

            $process->setTask($task);
        }

        return $process;
    }

    public function loadFromTask(Task $task): Process\Process
    {
        try {
            $process = $this->load($task->getCode());
            $process->setTask($task);

            $inputsData = json_decode($task->getInputs(), true);
            if (!is_array($inputsData)) {
                throw new InputException('Invalid Inputs Data from Task #' . $task->getId());
            }
            foreach ($inputsData as $key => $value) {
                $process->getInputs()->set($key, $value);
            }
        } catch (Exception $e) {
            $task->incrementTry($e->getMessage(), false);
            $task->setStatus(Status::FAILED);
            $this->entityManager->persist($task);
            $this->entityManager->flush();
            throw $e;
        }

        return $process;
    }

    private function loadPrepareSteps(array $processDefinition): array
    {
        $steps = [];

        foreach ($processDefinition['steps'] as $stepDefinition) {
            $step = $this->loadPrepareStep($stepDefinition);
            $steps[$step->getCode()] = $step;
        }

        return $steps;
    }

    private function loadPrepareStep(array $stepDefinition): Process\Step
    {
        return new Process\Step(
            $stepDefinition['code'],
            $this->configReader->getStepClassFromClassname($stepDefinition['class']),
            $this->loadPrepareParameters($stepDefinition['parameters']),
            $stepDefinition['ignore_in_progress']
        );
    }

    private function loadPrepareParameters(array $parametersDefinition): Process\Parameters
    {
        return new Process\Parameters($parametersDefinition);
    }

    private function loadPrepareInputs(array $inputsDefinition): Process\Inputs
    {
        return $this->inputsFactory->create($inputsDefinition);
    }

    private function loadPrepareOptions(array $optionsDefinition): Process\Options
    {
        return new Process\Options($optionsDefinition);
    }

    private function loadPrepareTask(string $processCode): Task
    {
        $task = new Task();

        $task
            ->setCode($processCode)
            ->setInputs("[]")
            ->setStatus(Status::CREATED)
            ->setTryNumber(0)
            ->setTryLastAt(null)
            ->setScheduledAt(null)
            ->setExecutedAt(null);

        return $task;
    }

    public function execute(Process\Process $process, callable $initCallback = null): mixed
    {
        $blockingTaskId = $this->getBlockingTaskId($process);
        if ($blockingTaskId !== null) {
            throw new ProcessException(
                'This process can not be executed, because it is locked by another one - Task #' . $blockingTaskId
            );
        }

        if ($process->getTask()) {
            $process->getTask()->setExecutedAt(new DateTime());
            $process->getTask()->setPidValue(getmypid());
            $process->getTask()->setPidLastSeen(new DateTime());
        }

        $this->executeUpdateTask($process, Status::RUNNING);

        $logger = clone $this->logger;
        $logger->setLastException(null);

        if ($this->loggerOutput) {
            $logger->setLoggerOutput($this->loggerOutput);
        }

        $nbSteps = $this->countMatterSteps($process);
        $logId = $logger->init($process->getCode(), $nbSteps, $process->getTask());
        $process->setLogId($logId);

        if ($initCallback) {
            call_user_func($initCallback, $process);
        }

        try {
            return $this->manageExecute($process, $logger);
        } catch (StepException $e) {
            $this->manageExecuteError(
                $process,
                $logger,
                $e,
                ($e->canBeRerunAutomatically() && $process->getOptions()->canBeRerunAutomatically())
            );

            throw $e;
        } catch (Exception $e) {
            $this->manageExecuteError(
                $process,
                $logger,
                $e,
                false
            );

            throw $e;
        }
    }

    private function countMatterSteps(Process\Process $process): int
    {
        $nbSteps = 0;

        foreach ($process->getSteps() as $step) {
            if (!$step->isIgnoreInProgress()) {
                $nbSteps++;
            }
        }

        return ($nbSteps > 0) ? $nbSteps : 1;
    }

    public function scheduleExecution(Process\Process $process, DateTimeInterface $scheduleDate): int
    {
        if (!$process->getOptions()->canBePutInQueue()) {
            throw new ProcessException(
                'This process can not be scheduled, because it can not be put in queue'
            );
        }

        $this->prepareInputs($process);
        $process->getTask()->setScheduledAt($scheduleDate);
        $this->executeUpdateTask($process, Status::CREATED);

        return $process->getTask()->getId();
    }

    public function executeAsynchronously(Process\Process $process): int
    {
        if (!$process->getOptions()->canBePutInQueue()) {
            throw new ProcessException(
                'This process can not be executed asynchronously, because it can not be put in queue'
            );
        }

        if ($this->getBlockingTaskId($process) !== null) {
            return $this->scheduleExecution($process, new DateTime());
        }

        $this->prepareInputs($process);
        $this->executeUpdateTask($process, Status::CREATED);

        $this->asynchronousCommand->execute('spipu:process:rerun', [$process->getTask()->getId()]);

        return $process->getTask()->getId();
    }

    public function getBlockingTaskId(Process\Process $process): ?int
    {
        $processLocks = $process->getOptions()->getProcessLocks();
        if (count($processLocks) === 0) {
            return null;
        }

        $query = $this->buildLockQuery(
            $processLocks,
            $process->getOptions()->canProcessLockOnFailed(),
            ($process->getTask() ? $process->getTask()->getId() : null)
        );

        try {
            $result = $this->entityManager->getConnection()->executeQuery($query)->fetchOne();
            return empty($result) ? null : (int) $result;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @param array $processLocks
     * @param bool $lockOnFailed
     * @param int|null $taskId
     * @return string
     * @SuppressWarnings(PMD.BooleanArgumentFlag)
     */
    private function buildLockQuery(array $processLocks, bool $lockOnFailed, ?int $taskId): string
    {
        foreach ($processLocks as &$processLock) {
            $processLock = $this->entityManager->getConnection()->quote($processLock);
        }

        $taskLowest  = '';
        $taskNotSame = '';
        if ($taskId !== null) {
            $taskLowest  = ' AND id < ' . $taskId;
            $taskNotSame = ' AND id <> ' . $taskId;
        }

        $where = [];
        $where[] = '(`status` = \'' . Status::CREATED . '\' AND `scheduled_at` IS NULL' . $taskLowest . ')';
        $where[] = '(`status` = \'' . Status::RUNNING . '\')';
        if ($lockOnFailed) {
            $where[] = '(`status` = \'' . Status::FAILED . '\'' . $taskNotSame . ')';
        }

        $query = 'SELECT `id` FROM `spipu_process_task` WHERE `code` IN (' . implode(',', $processLocks) . ')';
        $query .= ' AND (' . implode(' OR ', $where) . ')';
        $query .= ' ORDER BY `id` ASC LIMIT 1';

        return $query;
    }

    private function executePrepareOptions(Process\Process $process, LoggerProcessInterface $logger): void
    {
        foreach ($process->getOptions()->getOptions() as $key => $value) {
            $logger->debug(
                sprintf(
                    'Option [%s]: %s',
                    $key,
                    ($value ? 'Yes' : 'No')
                )
            );
        }
    }

    private function executePrepareInputs(Process\Process $process, LoggerProcessInterface $logger): void
    {
        $this->prepareInputs($process);

        foreach ($process->getInputs()->getInputs() as $input) {
            $name  = $input->getName();
            $type  = $input->getType();
            $value = $input->getValue();

            $logger->debug(
                sprintf(
                    'Input [%s] (%s): %s',
                    $name,
                    $type,
                    (is_array($value) ? json_encode($value) : $value)
                )
            );

            if ($input->getType() === 'file') {
                $value = $this->fileManager->getInputFilePath($process, $input, $value);
            }

            $process->getParameters()->set('input.' . $name, $value);
        }
    }

    private function prepareInputs(Process\Process $process): void
    {
        if ($process->getTask()) {
            $process->getTask()->setInputs(json_encode($process->getInputs()->getAll()));
        }

        $process->getInputs()->validate();
    }

    private function executeSteps(Process\Process $process, LoggerProcessInterface $logger): mixed
    {
        $kSteps = -1;
        $result = null;
        foreach ($process->getSteps() as $step) {
            if (!$step->isIgnoreInProgress()) {
                $kSteps++;
            }
            $logger->setCurrentStep(max($kSteps, 0), $step->isIgnoreInProgress());
            $logger->info(sprintf('Step [%s]', $step->getCode()));

            $stepProcessor = $step->getProcessor();

            $message = 'Step [' . $step->getCode() . ']';
            $this->reportManager->addProcessReportWarning($process, $message);
            $this->reportManager->addReportToStep($stepProcessor, $process->getReport());

            $startTime = microtime(true);
            $result = $stepProcessor->execute($step->getParameters(), $logger);
            $deltaTime = microtime(true) - $startTime;

            $message = 'Step executed in ' . number_format($deltaTime, 3, '.', '') . ' ms';
            $this->reportManager->addProcessReportMessage($process, $message);
            $this->reportManager->addReportToStep($stepProcessor, null);

            $process->getParameters()->set('time.' . $step->getCode(), $deltaTime);
            $process->getParameters()->set('result.' . $step->getCode(), $result);
        }
        $kSteps++;
        $logger->setCurrentStep($kSteps, false);

        return $result;
    }

    /**
     * @param Process\Process $process
     * @param string $status
     * @param string|null $message
     * @param bool $canBeRerunAutomatically
     * @return void
     * @SuppressWarnings(PMD.BooleanArgumentFlag)
     */
    private function executeUpdateTask(
        Process\Process $process,
        string $status,
        string $message = null,
        bool $canBeRerunAutomatically = false
    ): void {
        $task = $process->getTask();

        if ($task) {
            if ($status === Status::FAILED) {
                $task->incrementTry($message, $canBeRerunAutomatically);
            }

            $task->setStatus($status);
            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }
    }

    private function manageExecute(Process\Process $process, LoggerProcessInterface $logger): mixed
    {
        $this->executePrepareOptions($process, $logger);
        $this->executePrepareInputs($process, $logger);
        $this->executeUpdateTask($process, Status::RUNNING);

        $this->reportManager->prepareReport($process, $logger);

        $result = $this->executeSteps($process, $logger);

        $message = sprintf('Process Finished [%s]', $process->getCode());

        $logger->info($message);
        $logger->finish(Status::FINISHED);

        $this->executeUpdateTask($process, Status::FINISHED);

        $this->reportManager->addProcessReportWarning($process, $message);
        $this->reportManager->sendReport($process);

        return $result;
    }

    private function manageExecuteError(
        Process\Process $process,
        LoggerProcessInterface $logger,
        Throwable $exception,
        bool $rerun
    ): void {
        $logger->critical((string) $exception);

        $logger->warning(
            sprintf(
                'Can we rerun the process automatically after this error: [%s]',
                ($rerun ? 'Yes' : 'No')
            )
        );
        $logger->setLastException($exception);
        $logger->finish(Status::FAILED);

        $this->executeUpdateTask($process, Status::FAILED, $exception->getMessage(), $rerun);

        $this->reportManager->addProcessReportError($process, 'ERROR DURING TASK EXECUTION');
        $this->reportManager->addProcessReportError($process, $exception->getMessage());
        $this->reportManager->sendReport($process);
    }
}
