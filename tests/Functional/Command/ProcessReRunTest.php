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

use DateTime;
use Spipu\ConfigurationBundle\Service\ConfigurationManager;
use Spipu\ProcessBundle\Command\ProcessReRunCommand;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\ProcessManager;
use Spipu\ProcessBundle\Tests\Functional\AbstractFunctionalTest;
use Throwable;

class ProcessReRunTest extends AbstractFunctionalTest
{
    public function testExecuteMissingTaskId()
    {
        $commandTester = self::loadCommand(ProcessReRunCommand::class, 'spipu:process:rerun');

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "task-id").');

        $commandTester->execute([]);
    }

    public function testExecuteBadTaskId()
    {
        $commandTester = self::loadCommand(ProcessReRunCommand::class, 'spipu:process:rerun');

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('The asked task does not exist');

        $commandTester->execute(['task-id' => 42]);
    }

    public function testExecuteDisable()
    {
        $configurationManager = self::getContainer()->get(ConfigurationManager::class);
        $configurationManager->set('process.task.can_execute', 0);
        $configurationManager->clearCache();

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Execution is disabled in module configuration');

        try {
            $commandTester = self::loadCommand(ProcessReRunCommand::class, 'spipu:process:rerun');
            $commandTester->execute(['task-id' => 42]);
        } finally {
            $configurationManager = self::getContainer()->get(ConfigurationManager::class);
            $configurationManager->set('process.task.can_execute', 1);
            $configurationManager->clearCache();
        }
    }

    public function testExecuteFinishedTask()
    {
        $manager = self::getContainer()->get(ProcessManager::class);
        $process = $manager->load('test_simple');
        $process->getInputs()->set('automatic_report_email', 'foo@bar.fr');
        $process->getInputs()->set('name_from', 'Foo');
        $process->getInputs()->set('name_to', 'Bar');
        $manager->execute($process);
        $taskId = $process->getTask()->getId();

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('The asked task [2] with the status [finished] can not be rerun');

        $commandTester = self::loadCommand(ProcessReRunCommand::class, 'spipu:process:rerun');
        $commandTester->execute(['task-id' => $taskId]);
    }

    public function testExecuteCreatedTaskWithoutDebug()
    {
        $manager = self::getContainer()->get(ProcessManager::class);
        $process = $manager->load('test_simple');
        $process->getInputs()->set('automatic_report_email', 'foo@bar.fr');
        $process->getInputs()->set('name_from', 'Foo');
        $process->getInputs()->set('name_to', 'Bar');
        $manager->scheduleExecution($process, new DateTime());
        $taskId = $process->getTask()->getId();

        $commandTester = self::loadCommand(ProcessReRunCommand::class, 'spipu:process:rerun');
        $commandTester->execute(['task-id' => $taskId]);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('Rerun task #' . $taskId, $output);
        $this->assertStringContainsString('- Process: test_simple', $output);
        $this->assertStringContainsString('- Status: created', $output);
        $this->assertStringNotContainsString('Enable Debug Output', $output);
        $this->assertStringNotContainsString('[info___] Process Started [test_simple]', $output);
        $this->assertStringContainsString('=> Result:', $output);
        $this->assertStringContainsString('Hello World Bar from Foo', $output);
    }

    public function testExecuteCreatedTaskWithDebug()
    {
        $manager = self::getContainer()->get(ProcessManager::class);
        $process = $manager->load('test_simple');
        $process->getInputs()->set('automatic_report_email', 'foo@bar.fr');
        $process->getInputs()->set('name_from', 'Foo');
        $process->getInputs()->set('name_to', 'Bar');
        $manager->scheduleExecution($process, new DateTime());
        $taskId = $process->getTask()->getId();

        $commandTester = self::loadCommand(ProcessReRunCommand::class, 'spipu:process:rerun');
        $commandTester->execute(['task-id' => $taskId, '--debug' => 1]);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('Rerun task #' . $taskId, $output);
        $this->assertStringContainsString('- Process: test_simple', $output);
        $this->assertStringContainsString('- Status: created', $output);
        $this->assertStringContainsString('Enable Debug Output', $output);
        $this->assertStringContainsString('[info___] Process Started [test_simple]', $output);
        $this->assertStringContainsString('=> Result:', $output);
        $this->assertStringContainsString('Hello World Bar from Foo', $output);
    }
}
