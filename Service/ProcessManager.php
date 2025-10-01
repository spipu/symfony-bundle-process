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
use Spipu\CoreBundle\Exception\AsynchronousCommandException;
use Spipu\CoreBundle\Service\AsynchronousCommand;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Entity\Process;
use Spipu\ProcessBundle\Exception\InputException;
use Spipu\ProcessBundle\Exception\OptionException;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Exception\StopExecutionException;
use Throwable;

/**
 * Class Manager
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 * @SuppressWarnings(PMD.ExcessiveClassComplexity)
 */
class ProcessManager
{
    /**
     * @var ConfigReader
     */
    private ConfigReader $configReader;

    /**
     * @var MainParameters
     */
    private MainParameters $mainParameters;

    /**
     * @var LoggerProcessInterface
     */
    private LoggerProcessInterface $logger;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var AsynchronousCommand
     */
    private AsynchronousCommand $asynchronousCommand;

    /**
     * @var InputsFactory
     */
    private InputsFactory $inputsFactory;

    /**
     * @var ReportManager
     */
    private ReportManager $reportManager;

    /**
     * @var ModuleConfiguration
     */
    private ModuleConfiguration $moduleConfiguration;

    /**
     * @var LoggerOutputInterface|null
     */
    private ?LoggerOutputInterface $loggerOutput = null;

    /**
     * Manager constructor.
     * @param ConfigReader $configReader
     * @param MainParameters $mainParameters
     * @param LoggerProcessInterface $logger
     * @param EntityManagerInterface $entityManager
     * @param AsynchronousCommand $asynchronousCommand
     * @param InputsFactory $inputsFactory
     * @param ReportManager $reportManager
     * @param ModuleConfiguration $moduleConfiguration
     */
    public function __construct(
        ConfigReader $configReader,
        MainParameters $mainParameters,
        LoggerProcessInterface $logger,
        EntityManagerInterface $entityManager,
        AsynchronousCommand $asynchronousCommand,
        InputsFactory $inputsFactory,
        ReportManager $reportManager,
        ModuleConfiguration $moduleConfiguration
    ) {
        $this->configReader = $configReader;
        $this->mainParameters = $mainParameters;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->asynchronousCommand = $asynchronousCommand;
        $this->inputsFactory = $inputsFactory;
        $this->reportManager = $reportManager;
        $this->moduleConfiguration = $moduleConfiguration;
    }

    /**
     * Get the config reader
     * @return ConfigReader
     */
    public function getConfigReader(): ConfigReader
    {
        return $this->configReader;
    }

    /**
     * @param LoggerOutputInterface|null $loggerOutput
     * @return void
     */
    public function setLoggerOutput(?LoggerOutputInterface $loggerOutput): void
    {
        $this->loggerOutput = $loggerOutput;
    }

    /**
     * Init a process
     * @param string $code
     * @return Process\Process
     * @throws ProcessException
     */
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

    /**
     * @param Task $task
     * @return Process\Process
     * @throws Exception
     */
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

    /**
     * Prepare the list of the steps
     * @param array $processDefinition
     * @return Process\Step[]
     */
    private function loadPrepareSteps(array $processDefinition): array
    {
        $steps = [];

        foreach ($processDefinition['steps'] as $stepDefinition) {
            $step = $this->loadPrepareStep($stepDefinition);
            $steps[$step->getCode()] = $step;
        }

        return $steps;
    }

    /**
     * Prepare a step
     * @param array $stepDefinition
     * @return Process\Step
     */
    private function loadPrepareStep(array $stepDefinition): Process\Step
    {
        return new Process\Step(
            $stepDefinition['code'],
            $this->configReader->getStepClassFromClassname($stepDefinition['class']),
            $this->loadPrepareParameters($stepDefinition['parameters']),
            $stepDefinition['ignore_in_progress']
        );
    }

    /**
     * Prepare parameters
     * @param array $parametersDefinition
     * @return Process\Parameters
     */
    private function loadPrepareParameters(array $parametersDefinition): Process\Parameters
    {
        return new Process\Parameters($parametersDefinition);
    }

    /**
     * @param array $inputsDefinition
     * @return Process\Inputs
     * @throws InputException
     */
    private function loadPrepareInputs(array $inputsDefinition): Process\Inputs
    {
        return $this->inputsFactory->create($inputsDefinition);
    }

    /**
     * @param bool[] $optionsDefinition
     * @return Process\Options
     * @throws OptionException
     */
    private function loadPrepareOptions(array $optionsDefinition): Process\Options
    {
        return new Process\Options($optionsDefinition);
    }

    /**
     * @param string $processCode
     * @return Task
     */
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

    /**
     * Execute the process
     * @param Process\Process $process
     * @param callable|null $initCallback
     * @return mixed
     * @throws Exception
     */
    public function execute(Process\Process $process, callable $initCallback = null)
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

        $logger = $this->initProcessLogger($process);

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

    /**
     * @param Process\Process $process
     * @return int
     */
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

    /**
     * @param Process\Process $process
     * @param DateTimeInterface $scheduleDate
     * @return int
     * @throws InputException
     * @throws ProcessException
     */
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

    /**
     * @param Process\Process $process
     * @return int
     * @throws Exception
     */
    public function executeAsynchronously(Process\Process $process): int
    {
        if (!$process->getOptions()->canBePutInQueue()) {
            throw new ProcessException(
                'This process can not be executed asynchronously, because it can not be put in queue'
            );
        }

        if ($this->moduleConfiguration->hasTaskForceScheduleForAsync()) {
            return $this->scheduleExecution($process, new DateTime());
        }

        if ($this->getBlockingTaskId($process) !== null) {
            return $this->scheduleExecution($process, new DateTime());
        }

        $this->prepareInputs($process);
        $this->executeUpdateTask($process, Status::CREATED);

        try {
            $this->asynchronousCommand->execute('spipu:process:rerun', [$process->getTask()->getId()]);
        } catch (AsynchronousCommandException $exception) {
            $logger = $this->initProcessLogger($process);
            $this->manageExecuteError($process, $logger, $exception, true);
        }

        return $process->getTask()->getId();
    }

    /**
     * @param Process\Process $process
     * @return int|null
     */
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

     /**
     * @param Process\Process $process
     * @param LoggerProcessInterface $logger
     * @return void
     */
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

    /**
     * @param Process\Process $process
     * @param LoggerProcessInterface $logger
     * @return void
     * @throws InputException
     */
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
            $process->getParameters()->set('input.' . $name, $value);
        }
    }

    /**
     * @param Process\Process $process
     * @return void
     * @throws InputException
     */
    private function prepareInputs(Process\Process $process): void
    {
        if ($process->getTask()) {
            $process->getTask()->setInputs(json_encode($process->getInputs()->getAll()));
        }

        $process->getInputs()->validate();
    }

    /**
     * @param Process\Process $process
     * @param LoggerProcessInterface $logger
     * @return mixed|null
     * @throws StepException
     */
    private function executeSteps(Process\Process $process, LoggerProcessInterface $logger)
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

    /**
     * @param Process\Process $process
     * @param LoggerProcessInterface $logger
     * @return mixed
     * @throws InputException
     * @throws StepException
     */
    private function manageExecute(Process\Process $process, LoggerProcessInterface $logger)
    {
        $this->executePrepareOptions($process, $logger);
        $this->executePrepareInputs($process, $logger);
        $this->executeUpdateTask($process, Status::RUNNING);

        $this->reportManager->prepareReport($process, $logger);

        try {
            $result = $this->executeSteps($process, $logger);
        } catch (StopExecutionException $exception) {
            $logger->critical((string) $exception);
            $logger->warning('A "Stop Execution" exception was thrown, the process will be stopped without failure');
            $result = false;
        }

        $message = sprintf('Process Finished [%s]', $process->getCode());

        $logger->info($message);
        $logger->finish(Status::FINISHED);

        $this->executeUpdateTask($process, Status::FINISHED);

        $this->reportManager->addProcessReportWarning($process, $message);
        $this->reportManager->sendReport($process);

        return $result;
    }

    /**
     * @param Process\Process $process
     * @param LoggerProcessInterface $logger
     * @param Throwable $exception
     * @param bool $rerun
     * @return void
     */
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

    /**
     * @param Process\Process $process
     * @return LoggerProcessInterface
     */
    private function initProcessLogger(Process\Process $process): LoggerProcessInterface
    {
        $logger = clone $this->logger;
        $logger->setLastException(null);

        if ($this->loggerOutput) {
            $logger->setLoggerOutput($this->loggerOutput);
        }

        $nbSteps = $this->countMatterSteps($process);
        $logId = $logger->init($process->getCode(), $nbSteps, $process->getTask());
        $process->setLogId($logId);

        return $logger;
    }
}
