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
use Spipu\ProcessBundle\Service\TaskManager;
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

    public function testExecuteActionCheckPidHosts(): void
    {
        $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');

        $currentHost = self::getContainer()->get(TaskManager::class)->getCurrentHost();

        $otherHostOld = $this->createRunningTask(99999999, 'other-host', '-PT2H');
        $otherHostRecent = $this->createRunningTask(99999999, 'other-host', '-PT20M');
        $currentHostAlive = $this->createRunningTask(getmypid(), $currentHost, '-PT10M');
        $currentHostDead = $this->createRunningTask(99999999, $currentHost, '-PT10M');

        // Child: only the tasks of the current host are checked
        $result = $commandTester->execute(['cron_action' => 'check-pid', '--is-child' => true]);
        $this->assertSame(Command::SUCCESS, $result);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('Search running tasks', $output);
        $this->assertStringNotContainsString('Search running tasks not seen on other hosts', $output);
        $this->assertStringNotContainsString('Search waiting tasks', $output);

        $this->assertTaskStatus($otherHostOld, 'running');
        $this->assertTaskStatus($otherHostRecent, 'running');
        $this->assertTaskStatus($currentHostAlive, 'running');
        $this->assertTaskStatus($currentHostDead, 'failed', 'Process not found');

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $task = $entityManager->getRepository(Task::class)->find($currentHostAlive);
        $this->assertGreaterThan($this->getRelativeDate('-PT1M'), $task->getPidLastSeen());

        // Main (default): the tasks of the other hosts not seen for too long are also failed
        $result = $commandTester->execute(['cron_action' => 'check-pid']);
        $this->assertSame(Command::SUCCESS, $result);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('Search running tasks not seen on other hosts', $output);

        $this->assertTaskStatus(
            $otherHostOld,
            'failed',
            'Process not seen for more than 30 minute(s) on host [other-host]'
        );
        $this->assertTaskStatus($otherHostRecent, 'running');
        $this->assertTaskStatus($currentHostAlive, 'running');
    }

    private function createRunningTask(int $pidValue, ?string $pidHost, string $pidLastSeen): int
    {
        $task = new Task();
        $task
            ->setCode('test')
            ->setInputs('[]')
            ->setStatus('running')
            ->setCanBeRerunAutomatically(true)
            ->setPidValue($pidValue)
            ->setPidHost($pidHost)
            ->setPidLastSeen($this->getRelativeDate($pidLastSeen));

        $entityManager = $this->getEntityManager();
        $entityManager->persist($task);
        $entityManager->flush();

        return $task->getId();
    }

    private function assertTaskStatus(int $taskId, string $status, ?string $tryLastMessage = null): void
    {
        // The command uses the entity manager of the last booted kernel
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->clear();

        $task = $entityManager->getRepository(Task::class)->find($taskId);
        $this->assertSame($status, $task->getStatus());
        if ($tryLastMessage !== null) {
            $this->assertSame($tryLastMessage, $task->getTryLastMessage());
        }
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
