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
use Spipu\ProcessBundle\Service\CronManager;
use Spipu\ProcessBundle\Service\ModuleConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessCronManagerCommand extends Command
{
    public const ARGUMENT_ACTION = 'cron_action';

    private CronManager $cronManager;
    private ModuleConfiguration $processConfiguration;
    private array $availableActions = [
        'rerun'     => 'actionRerun',
        'cleanup'   => 'actionCleanup',
        'check-pid' => 'actionCheckRunningTasks',
    ];

    public function __construct(
        CronManager $cronManager,
        ModuleConfiguration $processConfiguration,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->cronManager = $cronManager;
        $this->processConfiguration = $processConfiguration;
    }

    protected function configure(): void
    {
        $this
            ->setName('spipu:process:cron-manager')
            ->setDescription('Run the process manager (automatic relaunch, log cleaner, ...')
            ->setHelp('This command allows you to run the process manager')
            ->addArgument(
                static::ARGUMENT_ACTION,
                InputArgument::REQUIRED,
                'The code of the cron action to execute : [' . implode('|', array_keys($this->availableActions)) . ']'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->processConfiguration->hasTaskCanExecute()) {
            throw new ProcessException('Execution is disabled in module configuration');
        }

        $action = $input->getArgument(static::ARGUMENT_ACTION);
        if (!array_key_exists($action, $this->availableActions)) {
            throw new InvalidArgumentException('The asked action is not allowed');
        }

        $this->{$this->availableActions[$action]}($output);

        return self::SUCCESS;
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

    /**
     * @param OutputInterface $output
     * @return void
     * @SuppressWarnings(PMD.UnusedPrivateMethod)
     */
    private function actionCheckRunningTasks(OutputInterface $output): void
    {
        $output->writeln(Date('Y-m-d H:i:s') . ' - Process Cron Manager - Check Running Tasks - Begin');
        $this->cronManager->checkRunningTasksPid($output);
        $output->writeln(Date('Y-m-d H:i:s') . ' - Process Cron Manager - Check Running Tasks - End');
    }
}
