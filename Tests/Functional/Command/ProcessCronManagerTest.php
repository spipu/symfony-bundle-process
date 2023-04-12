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

use Spipu\ProcessBundle\Command\ProcessCronManagerCommand;
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
}
