<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Command;

use Exception;
use Spipu\ProcessBundle\Repository\TaskRepository;
use Spipu\ProcessBundle\Service\LoggerOutput;
use Spipu\ProcessBundle\Service\Status as ProcessStatus;
use Spipu\ProcessBundle\Service\Manager as ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessReRunCommand extends Command
{
    const ARGUMENT_TASK = 'task-id';
    const OPTION_DEBUG = 'debug';

    /**
     * @var TaskRepository
     */
    private $processTaskRepository;

    /**
     * @var ProcessManager
     */
    private $processManager;

    /**
     * @var ProcessStatus
     */
    private $processStatus;

    /**
     * RunProcess constructor.
     * @param TaskRepository $processTaskRepository
     * @param ProcessManager $processManager
     * @param ProcessStatus $processStatus
     * @param null|string $name
     */
    public function __construct(
        TaskRepository $processTaskRepository,
        ProcessManager $processManager,
        ProcessStatus $processStatus,
        ?string $name = null
    ) {
        $this->processTaskRepository = $processTaskRepository;
        $this->processManager = $processManager;

        parent::__construct($name);
        $this->processStatus = $processStatus;
    }

    /**
     * Configure the command
     *
     * @return void
     */
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

    /**
     * Ask for missing arguments and options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->getArgument(static::ARGUMENT_TASK)) {
            $description = [];
            $description[] = '';
            $description[] = 'You must provide an existing failed task id.';

            $output->writeln($description);
        }
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $taskId = (int) $input->getArgument(static::ARGUMENT_TASK);

        $output->writeln('Rerun task #'.$taskId);

        $task = $this->processTaskRepository->find($taskId);
        if (!$task) {
            throw new Exception('The asked task does not exist');
        }
        if (!$this->processStatus->canRerun($task->getStatus())) {
            throw new Exception(
                sprintf(
                    'The asked task [%s] with the status [%s] can not be rerun',
                    $taskId,
                    $task->getStatus()
                )
            );
        }

        $output->writeln(' - Process: '.$task->getCode());
        $output->writeln(' - Status: '.$task->getStatus());

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
        $output->writeln($result);

        return 0;
    }
}
