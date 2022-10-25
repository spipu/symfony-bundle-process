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
    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var MainParameters
     */
    private $mainParameters;

    /**
     * @var LoggerProcessInterface
     */
    private $logger;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AsynchronousCommand
     */
    private $asynchronousCommand;

    /**
     * @var InputsFactory
     */
    private $inputsFactory;

    /**
     * @var LoggerOutputInterface|null
     */
    private $loggerOutput;

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
            $this->executePrepareOptions($process, $logger);
            $this->executePrepareInputs($process, $logger);
            $this->executeUpdateTask($process, Status::RUNNING);

            $result = $this->executeSteps($process, $logger);

            $logger->info(sprintf('Process Finished [%s]', $process->getCode()));
            $logger->finish(Status::FINISHED);

            $this->executeUpdateTask($process, Status::FINISHED);
        } catch (StepException $e) {
            $rerun = ($e->canBeRerunAutomatically() && $process->getOptions()->canBeRerunAutomatically());

            $logger->critical((string) $e);
            $logger->warning(
                sprintf(
                    'Can we rerun the process automatically after this error: [%s]',
                    ($rerun ? 'Yes' : 'No')
                )
            );
            $logger->setLastException($e);
            $logger->finish(Status::FAILED);

            $this->executeUpdateTask($process, Status::FAILED, $e->getMessage(), $rerun);
            throw $e;
        } catch (Exception $e) {
            $logger->critical((string) $e);
            $logger->warning(
                sprintf(
                    'Can we rerun the process automatically after this error: [%s]',
                    'No'
                )
            );
            $logger->setLastException($e);
            $logger->finish(Status::FAILED);

            $this->executeUpdateTask($process, Status::FAILED, $e->getMessage(), false);
            throw $e;
        }

        return $result;
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

            $startTime = microtime(true);

            $result = $step->getProcessor()->execute($step->getParameters(), $logger);

            $process->getParameters()->set('time.' . $step->getCode(), microtime(true) - $startTime);
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
}
