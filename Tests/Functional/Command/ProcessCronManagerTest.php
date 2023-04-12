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
}
