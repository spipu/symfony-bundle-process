<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Command;

use Spipu\ProcessBundle\Service\CronManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessCronManagerCommand extends Command
{
    const ARGUMENT_ACTION = 'cron_action';

    /**
     * @var CronManager
     */
    private $cronManager;

    /**
     * @var array
     */
    private $availableActions = [
        'rerun'   => 'actionRerun',
        'cleanup' => 'actionCleanup'
    ];

    /**
     * RunProcess constructor.
     * @param CronManager $cronManager
     * @param null|string $name
     */
    public function __construct(
        CronManager $cronManager,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->cronManager = $cronManager;
    }

    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('spipu:process:cron-manager')
            ->setDescription('Run the process manager (automatic relaunch, log cleaner, ...')
            ->setHelp('This command allows you to run the process manager')
            ->addArgument(
                static::ARGUMENT_ACTION,
                InputArgument::REQUIRED,
                'The code of the cron action to execute : ['.implode('|', array_keys($this->availableActions)).']'
            )
        ;
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $action = $input->getArgument(static::ARGUMENT_ACTION);
        if (!array_key_exists($action, $this->availableActions)) {
            throw new InvalidArgumentException('The asked action is not allowed');
        }

        $this->{$this->availableActions[$action]}($output);
    }

    /**
     * @param OutputInterface $output
     * @return void
     * @SuppressWarnings(PMD.UnusedPrivateMethod)
     */
    private function actionRerun(OutputInterface $output): void
    {
        $output->writeln(Date('Y-m-d H:i:s') . ' - Process Cron Manager - Rerun - Begin');
        $this->cronManager->rerunWaitingTasks($output);
        $output->writeln(Date('Y-m-d H:i:s') . ' - Process Cron Manager - Rerun - End');
    }

    /**
     * @param OutputInterface $output
     * @return void
     * @SuppressWarnings(PMD.UnusedPrivateMethod)
     */
    private function actionCleanUp(OutputInterface $output): void
    {
        $output->writeln(Date('Y-m-d H:i:s') . ' - Process Cron Manager - CleanUp - Begin');
        $this->cronManager->cleanFinishedTasks($output);
        $this->cronManager->cleanFinishedLogs($output);
        $output->writeln(Date('Y-m-d H:i:s') . ' - Process Cron Manager - CleanUp - End');
    }
}
