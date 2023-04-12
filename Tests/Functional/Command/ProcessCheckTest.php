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
use Spipu\CoreBundle\Tests\WebTestCase;
use Spipu\ProcessBundle\Command\ProcessCheckCommand;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\ProcessManager;

class ProcessCheckTest extends WebTestCase
{
    public function testExecuteWithoutStatus()
    {
        $commandTester = self::loadCommand(ProcessCheckCommand::class, 'spipu:process:check');
        $commandTester->execute([]);
        $this->assertSame("Number of tasks\n0", trim($commandTester->getDisplay()));
    }

    public function testExecuteWithGoodStatus()
    {
        $commandTester = self::loadCommand(ProcessCheckCommand::class, 'spipu:process:check');
        $commandTester->execute(['--status' => 'created']);
        $this->assertSame("Number of tasks in status [created]\n0", trim($commandTester->getDisplay()));
    }

    public function testExecuteWithBadStatus()
    {
        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Unknown status. Use one of created,running,finished,failed');

        $commandTester = self::loadCommand(ProcessCheckCommand::class, 'spipu:process:check');
        $commandTester->execute(['--status' => 'foo']);
    }

    public function testExecuteDirectStatus()
    {
        $commandTester = self::loadCommand(ProcessCheckCommand::class, 'spipu:process:check');
        $commandTester->execute(['--status' => 'created', '--direct' => 1]);
        $this->assertSame('0', trim($commandTester->getDisplay()));
    }

    public function testExecuteGoodCount()
    {
        $commandTester = self::loadCommand(ProcessCheckCommand::class, 'spipu:process:check');

        $commandTester->execute(['--direct' => 1]);
        $this->assertSame('0', trim($commandTester->getDisplay()));

        $commandTester->execute(['--status' => 'created', '--direct' => 1]);
        $this->assertSame('0', trim($commandTester->getDisplay()));

        $commandTester->execute(['--status' => 'finished', '--direct' => 1]);
        $this->assertSame('0', trim($commandTester->getDisplay()));

        $manager = self::getContainer()->get(ProcessManager::class);
        $process = $manager->load('test_sleep');
        $manager->scheduleExecution($process, new DateTime());

        $commandTester->execute(['--direct' => 1]);
        $this->assertSame('1', trim($commandTester->getDisplay()));

        $commandTester->execute(['--status' => 'created', '--direct' => 1]);
        $this->assertSame('1', trim($commandTester->getDisplay()));

        $commandTester->execute(['--status' => 'finished', '--direct' => 1]);
        $this->assertSame('0', trim($commandTester->getDisplay()));
    }
}
