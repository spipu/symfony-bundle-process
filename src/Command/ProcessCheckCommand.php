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
use Spipu\ProcessBundle\Service\Status;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessCheckCommand extends Command
{
    public const OPTION_STATUS = 'status';
    public const OPTION_DIRECT = 'direct';

    private Status $status;
    private TaskRepository $taskRepository;

    public function __construct(
        Status $status,
        TaskRepository $taskRepository,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->status = $status;
        $this->taskRepository = $taskRepository;
    }

    protected function configure(): void
    {
        $this
            ->setName('spipu:process:check')
            ->setDescription('Check the number of tasks.')
            ->setHelp('This command allows you to check the number of tasks')
            ->addOption(
                static::OPTION_STATUS,
                's',
                InputOption::VALUE_OPTIONAL,
                'Status to check'
            )
            ->addOption(
                static::OPTION_DIRECT,
                'd',
                InputOption::VALUE_NONE,
                'Display directly and only the number'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = $input->getOption(static::OPTION_STATUS);
        $direct = $input->getOption(static::OPTION_DIRECT);


        $label = 'Number of tasks';
        if ($status !== null) {
            $statuses = $this->status->getStatuses();
            if (!in_array($status, $statuses, true)) {
                throw new ProcessException('Unknown status. Use one of ' . implode(',', $statuses));
            }
            $label .= " in status [$status]";
        }
        $count = $this->taskRepository->countTasks($status);

        if (!$direct) {
            $output->writeln($label);
        }
        $output->writeln((string) $count);

        return self::SUCCESS;
    }
}
