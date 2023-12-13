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

namespace Spipu\ProcessBundle\Command;

use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Repository\TaskRepository;
use Spipu\ProcessBundle\Service\LoggerOutput;
use Spipu\ProcessBundle\Service\ModuleConfiguration;
use Spipu\ProcessBundle\Service\Status as ProcessStatus;
use Spipu\ProcessBundle\Service\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessReRunCommand extends Command
{
    public const ARGUMENT_TASK = 'task-id';
    public const OPTION_DEBUG = 'debug';

    private TaskRepository $processTaskRepository;
    private ProcessManager $processManager;
    private ProcessStatus $processStatus;
    private ModuleConfiguration $processConfiguration;

    public function __construct(
        TaskRepository $processTaskRepository,
        ProcessManager $processManager,
        ProcessStatus $processStatus,
        ModuleConfiguration $processConfiguration,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->processTaskRepository = $processTaskRepository;
        $this->processManager = $processManager;
        $this->processStatus = $processStatus;
        $this->processConfiguration = $processConfiguration;
    }

    protected function configure(): void
    {
        $this
            ->setName('spipu:process:rerun')
            ->setDescription('ReRun an existing failed process.')
            ->setHelp('This command allows you to re-run an existing failed process task')
            ->addArgument(
                static::ARGUMENT_TASK,
                InputArgument::REQUIRED,
                'The id of the task to re-run'
            )
            ->addOption(
                static::OPTION_DEBUG,
                'd',
                InputOption::VALUE_NONE,
                'Display the logs on console'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->getArgument(static::ARGUMENT_TASK)) {
            $description = [];
            $description[] = '';
            $description[] = 'You must provide an existing failed task id.';

            $output->writeln($description);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->processConfiguration->hasTaskCanExecute()) {
            throw new ProcessException('Execution is disabled in module configuration');
        }

        $taskId = (int) $input->getArgument(static::ARGUMENT_TASK);

        $output->writeln('Rerun task #' . $taskId);

        $task = $this->processTaskRepository->find($taskId);
        if (!$task) {
            throw new ProcessException('The asked task does not exist');
        }
        if (!$this->processStatus->canRerun($task->getStatus())) {
            throw new ProcessException(
                sprintf(
                    'The asked task [%s] with the status [%s] can not be rerun',
                    $taskId,
                    $task->getStatus()
                )
            );
        }

        $output->writeln(' - Process: ' . $task->getCode());
        $output->writeln(' - Status: ' . $task->getStatus());

        // Debug mode or not.
        $loggerOutput = null;
        if ($input->getOption(static::OPTION_DEBUG)) {
            $output->writeln('Enable Debug Output');
            $loggerOutput = new LoggerOutput($output);
        }

        $this->processManager->setLoggerOutput($loggerOutput);
        $process = $this->processManager->loadFromTask($task);
        $result = $this->processManager->execute($process);

        $output->writeln(' => Result:');
        $output->writeln((string) $result);

        return self::SUCCESS;
    }
}
