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

namespace Spipu\ProcessBundle\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use DateInterval;
use DateTime;
use Spipu\ConfigurationBundle\Service\ConfigurationManager;
use Spipu\ProcessBundle\Command\ProcessCronManagerCommand;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Event\WaitingTasksEvent;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Tests\Functional\AbstractFunctionalTestCase;
use Symfony\Component\Console\Command\Command;
use Throwable;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ProcessCronManagerCommand::class)]
class ProcessCronManagerTest extends AbstractFunctionalTestCase
{
    public function testExecuteMissingAction(): void
    {
        $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "cron_action").');

        $commandTester->execute([]);
    }

    public function testExecuteBadAction(): void
    {
        $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('The asked action is not allowed');

        $commandTester->execute(['cron_action' => 'foo']);
    }

    public function testExecuteDisableRerun(): void
    {
        $this->setCanExecute(false);

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Execution is disabled in module configuration');

        try {
            $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');
            $commandTester->execute(['cron_action' => 'rerun']);
        } finally {
            $this->setCanExecute(true);
        }
    }

    public function testExecuteDisableCleanup(): void
    {
        $this->setCanExecute(false);

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Execution is disabled in module configuration');

        try {
            $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');
            $commandTester->execute(['cron_action' => 'cleanup']);
        } finally {
            $this->setCanExecute(true);
        }
    }

    public function testExecuteDisableCheckPid(): void
    {
        $this->setCanExecute(false);

        try {
            $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');
            $result = $commandTester->execute(['cron_action' => 'check-pid']);
        } finally {
            $this->setCanExecute(true);
        }

        $this->assertSame(Command::SUCCESS, $result);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('Process Cron Manager - Check Running Tasks - Begin', $output);
        $this->assertStringContainsString('Process Cron Manager - Check Running Tasks - End', $output);
    }

    public function testExecuteActionRerun(): void
    {
        $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');

        $result = $commandTester->execute(['cron_action' => 'rerun']);

        $this->assertSame(Command::SUCCESS, $result);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('Process Cron Manager - Rerun - Begin', $output);
        $this->assertStringContainsString('Search tasks to execute automatically', $output);
        $this->assertStringContainsString('=> No task found', $output);
        $this->assertStringContainsString('Process Cron Manager - Rerun - End', $output);
    }

    public function testExecuteActionCleanup(): void
    {
        $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');

        $result = $commandTester->execute(['cron_action' => 'cleanup']);

        $this->assertSame(Command::SUCCESS, $result);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('Process Cron Manager - CleanUp - Begin', $output);
        $this->assertStringContainsString('Search finished tasks to clean', $output);
        $this->assertStringContainsString('=> Deleted Tasks: 0', $output);
        $this->assertStringContainsString('Search finished logs to clean', $output);
        $this->assertStringContainsString('> Deleted Logs: 0', $output);
        $this->assertStringContainsString('Process Cron Manager - CleanUp - End', $output);
    }

    public function testExecuteActionCheckPid(): void
    {
        $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');

        $result = $commandTester->execute(['cron_action' => 'check-pid']);

        $this->assertSame(Command::SUCCESS, $result);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('Process Cron Manager - Check Running Tasks - Begin', $output);
        $this->assertStringContainsString('Search running tasks', $output);
        $this->assertStringContainsString('=> No task found', $output);
        $this->assertStringContainsString('Search waiting tasks', $output);
        $this->assertStringContainsString('Process Cron Manager - Check Running Tasks - End', $output);
    }

    public function testExecuteActionCheckPidWaitingTasks(): void
    {
        $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');

        /** @var WaitingTasksEvent[] $events */
        $events = [];
        self::getContainer()->get('event_dispatcher')->addListener(
            'spipu.process.task.waiting',
            function (WaitingTasksEvent $event) use (&$events): void {
                $events[] = $event;
            }
        );

        $result = $commandTester->execute(['cron_action' => 'check-pid']);
        $this->assertSame(Command::SUCCESS, $result);
        $this->assertCount(0, $events);

        // Detected: created 2 hours ago / scheduled 2 hours ago
        $this->createTask('-PT2H', null);
        $this->createTask('-PT2H', null);
        $this->createTask('-PT3H', '-PT2H');

        // Not detected: too recent / scheduled recently or in the future / not in created status
        $this->createTask('-PT30M', null);
        $this->createTask('-PT3H', '-PT30M');
        $this->createTask('-PT3H', 'PT2H');
        $this->createTask('-PT2H', null, 'failed');

        $result = $commandTester->execute(['cron_action' => 'check-pid']);
        $this->assertSame(Command::SUCCESS, $result);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString(
            '=> 3 task(s) waiting for more than 60 minute(s): 2 unscheduled, 1 scheduled',
            $output
        );

        $this->assertCount(1, $events);
        $this->assertSame(2, $events[0]->getNbUnscheduledTasks());
        $this->assertSame(1, $events[0]->getNbScheduledTasks());
        $this->assertSame(3, $events[0]->getNbTasks());
        $this->assertSame(60, $events[0]->getWaitingDelay());
        $this->assertSame(true, $events[0]->hasTaskCanExecute());
    }

    private function createTask(string $createdAt, ?string $scheduledAt, string $status = 'created'): void
    {
        $task = new Task();
        $task
            ->setCode('test')
            ->setInputs('[]')
            ->setStatus($status)
            ->setCanBeRerunAutomatically(true)
            ->setScheduledAt($scheduledAt === null ? null : $this->getRelativeDate($scheduledAt));

        $entityManager = $this->getEntityManager();
        $entityManager->persist($task);
        $entityManager->flush();

        // The creation date is forced by the PrePersist callback, it must be updated directly in database.
        $entityManager->getConnection()->executeStatement(
            'update spipu_process_task set created_at = ? where id = ?',
            [$this->getRelativeDate($createdAt)->format('Y-m-d H:i:s'), $task->getId()]
        );
    }

    private function getRelativeDate(string $interval): DateTime
    {
        $date = new DateTime();
        if (str_starts_with($interval, '-')) {
            return $date->sub(new DateInterval(substr($interval, 1)));
        }

        return $date->add(new DateInterval($interval));
    }

    private function setCanExecute(bool $canExecute): void
    {
        $configurationManager = self::getContainer()->get(ConfigurationManager::class);
        $configurationManager->set('process.task.can_execute', $canExecute ? 1 : 0);
        $configurationManager->clearCache();
    }
}
