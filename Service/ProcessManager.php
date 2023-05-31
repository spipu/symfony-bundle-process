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
use Spipu\ProcessBundle\Step\StepReportInterface;
use Throwable;

/**
 * Class Manager
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 * @SuppressWarnings(PMD.ExcessiveClassComplexity)
 */
class ProcessManager
{
    public const AUTOMATIC_REPORT_EMAIL_FIELD = 'automatic_report_email';

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
     */
    public function __construct(
        ConfigReader $configReader,
        MainParameters $mainParameters,
        LoggerProcessInterface $logger,
        EntityManagerInterface $entityManager,
        AsynchronousCommand $asynchronousCommand,
        InputsFactory $inputsFactory
    ) {
        $this->configReader = $configReader;
        $this->mainParameters = $mainParameters;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->asynchronousCommand = $asynchronousCommand;
        $this->inputsFactory = $inputsFactory;
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
        if ($this->isProcessLockedByAnotherOne($process)) {
            throw new ProcessException(
                'This process can not be executed, because it is locked by another one'
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

        if ($this->isProcessLockedByAnotherOne($process)) {
            return $this->scheduleExecution($process, new DateTime());
        }

        $this->prepareInputs($process);
        $this->executeUpdateTask($process, Status::CREATED);

        $this->asynchronousCommand->execute('spipu:process:rerun', [$process->getTask()->getId()]);

        return $process->getTask()->getId();
    }

    /**
     * @param Process\Process $process
     * @return bool
     */
    public function isProcessLockedByAnotherOne(Process\Process $process): bool
    {
        $processLocks = $process->getOptions()->getProcessLocks();
        if (count($processLocks) === 0) {
            return false;
        }

        $query = $this->buildLockQuery(
            $processLocks,
            $process->getOptions()->canProcessLockOnFailed(),
            ($process->getTask() ? $process->getTask()->getId() : null)
        );

        try {
            $result = $this->entityManager->getConnection()->executeQuery($query)->fetchOne();
            return (!empty($result));
        } catch (Throwable $e) {
            return false;
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

        $statuses = [Status::CREATED, Status::RUNNING];
        if ($lockOnFailed) {
            $statuses[] = Status::FAILED;
        }
        foreach ($statuses as &$status) {
            $status = $this->entityManager->getConnection()->quote($status);
        }

        $query = sprintf(
            "SELECT `id` FROM `spipu_process_task` WHERE `code` IN (%s) AND `status` IN (%s)",
            implode(',', $processLocks),
            implode(',', $statuses)
        );

        if ($taskId !== null) {
            $query .= ' AND id < ' . $taskId;
        }
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
     * @return void
     * @throws InputException
     */
    private function executePrepareReport(Process\Process $process, LoggerProcessInterface $logger): void
    {
        if (!$process->getOptions()->hasAutomaticReport()) {
            return;
        }

        $email = $process->getInputs()->get(self::AUTOMATIC_REPORT_EMAIL_FIELD);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InputException('The automatic report email is invalid: ' . $email);
        }

        $report = new Process\Report($process->getInputs()->get(self::AUTOMATIC_REPORT_EMAIL_FIELD));
        $process->setReport($report);
        $logger->debug(sprintf('Automatic report will be sent to [%s]', $email));
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

            if ($process->getReport()) {
                $process->getReport()->addMessage('Step [' . $step->getCode() . ']');
                if ($stepProcessor instanceof StepReportInterface) {
                    $stepProcessor->setReport($process->getReport());
                }
            }

            $startTime = microtime(true);
            $result = $stepProcessor->execute($step->getParameters(), $logger);
            $deltaTime = microtime(true) - $startTime;

            if ($process->getReport()) {
                $process->getReport()->addMessage('Step executed in ' . number_format($deltaTime, 3, '.', '') . ' ms');
                if ($stepProcessor instanceof StepReportInterface) {
                    $stepProcessor->setReport(null);
                }
            }

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
    public function manageExecute(Process\Process $process, LoggerProcessInterface $logger)
    {
        $this->executePrepareOptions($process, $logger);
        $this->executePrepareInputs($process, $logger);
        $this->executePrepareReport($process, $logger);
        $this->executeUpdateTask($process, Status::RUNNING);

        $result = $this->executeSteps($process, $logger);

        $message = sprintf('Process Finished [%s]', $process->getCode());

        $logger->info($message);
        $logger->finish(Status::FINISHED);

        $this->executeUpdateTask($process, Status::FINISHED);

        if ($process->getReport()) {
            $process->getReport()->addMessage($message);
            $this->sendReport($process);
        }

        return $result;
    }

    /**
     * @param Process\Process $process
     * @param LoggerProcessInterface $logger
     * @param Throwable $exception
     * @param bool $rerun
     * @return void
     */
    public function manageExecuteError(
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

        if ($process->getReport()) {
            $process->getReport()->addError($exception->getMessage());
            $this->sendReport($process);
        }
    }

    /**
     * @param Process\Process $process
     * @return void
     */
    private function sendReport(Process\Process $process): void
    {
    }
}
