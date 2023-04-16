<?php

/**
 * This file is part of a Spipu Bundle
 *
 * (c) Laurent Minguet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spipu\ProcessBundle\Tests\Functional\Command;

use Spipu\ConfigurationBundle\Service\ConfigurationManager;
use Spipu\ProcessBundle\Command\ProcessCronManagerCommand;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Tests\Functional\AbstractFunctionalTest;
use Symfony\Component\Console\Command\Command;
use Throwable;

class ProcessCronManagerTest extends AbstractFunctionalTest
{
    public function testExecuteMissingAction()
    {
        $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "cron_action").');

        $commandTester->execute([]);
    }

    public function testExecuteBadAction()
    {
        $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('The asked action is not allowed');

        $commandTester->execute(['cron_action' => 'foo']);
    }

    public function testExecuteDisable()
    {
        $configurationManager = self::getContainer()->get(ConfigurationManager::class);
        $configurationManager->set('process.task.can_execute', 0);
        $configurationManager->clearCache();

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Execution is disabled in module configuration');

        try {
            $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');
            $commandTester->execute(['cron_action' => 'foo']);
        } finally {
            $configurationManager = self::getContainer()->get(ConfigurationManager::class);
            $configurationManager->set('process.task.can_execute', 1);
            $configurationManager->clearCache();
        }
    }

    public function testExecuteActionRerun()
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

    public function testExecuteActionCleanup()
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

    public function testExecuteActionCheckPid()
    {
        $commandTester = self::loadCommand(ProcessCronManagerCommand::class, 'spipu:process:cron-manager');

        $result = $commandTester->execute(['cron_action' => 'check-pid']);

        $this->assertSame(Command::SUCCESS, $result);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('Process Cron Manager - Check Running Tasks - Begin', $output);
        $this->assertStringContainsString('Search running tasks', $output);
        $this->assertStringContainsString('=> No task found', $output);
        $this->assertStringContainsString('Process Cron Manager - Check Running Tasks - End', $output);
    }
}
